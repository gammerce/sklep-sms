<?php
namespace Tests\Feature\Http\Api\Ipn;

use App\Payment\General\PaymentMethod;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\Concerns\SimPayConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class ChargeWalletTest extends HttpTestCase
{
    use PaymentModuleFactoryConcern;
    use SimPayConcern;

    private SettingsRepository $settingsRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsRepository = $this->app->make(SettingsRepository::class);
        $this->userRepository = $this->app->make(UserRepository::class);
    }

    /** @test */
    public function charges_using_transfer()
    {
        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $this->settingsRepository->update([
            "transfer_platform" => $paymentPlatform->getId(),
        ]);
        $user = $this->factory->user();
        $this->actingAs($user);

        $validationResponse = $this->post("/api/purchases", [
            "service_id" => ChargeWalletServiceModule::MODULE_ID,
            "payment_option" => make_charge_wallet_option(
                PaymentMethod::TRANSFER(),
                $paymentPlatform
            ),
            "transfer_price" => 40.8,
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);
        $transactionId = $json["transaction_id"];

        $paymentResponse = $this->post("/api/payment/{$transactionId}", [
            "method" => PaymentMethod::TRANSFER(),
            "payment_platform_id" => $paymentPlatform->getId(),
        ]);
        $this->assertSame(200, $paymentResponse->getStatusCode());
        $json = $this->decodeJsonResponse($paymentResponse);

        $response = $this->post("/api/ipn/transfer/{$paymentPlatform->getId()}", [
            "tr_id" => 1,
            "tr_amount" => "40.80",
            "tr_crc" => $json["data"]["data"]["crc"],
            "id" => 1,
            "test_mode" => 1,
            "md5sum" => md5(
                array_get($paymentPlatform->getData(), "account_id") .
                    "1" .
                    "40.80" .
                    $json["data"]["data"]["crc"] .
                    ""
            ),
            "tr_status" => "TRUE",
            "tr_error" => "none",
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertEqualsMoney(4080, $freshUser->getWallet());
    }

    /** @test */
    public function charges_using_direct_billing()
    {
        $this->mockSimPayIpList();
        $this->mockSimPayApiSuccessResponse();

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);
        $this->settingsRepository->update([
            "direct_billing_platform" => $paymentPlatform->getId(),
        ]);
        $user = $this->factory->user();
        $this->actingAs($user);

        $validationResponse = $this->post("/api/purchases", [
            "service_id" => ChargeWalletServiceModule::MODULE_ID,
            "payment_option" => make_charge_wallet_option(
                PaymentMethod::DIRECT_BILLING(),
                $paymentPlatform
            ),
            "direct_billing_price" => 2.5,
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);
        $transactionId = $json["transaction_id"];

        $paymentResponse = $this->post("/api/payment/{$transactionId}", [
            "method" => PaymentMethod::DIRECT_BILLING(),
            "payment_platform_id" => $paymentPlatform->getId(),
        ]);
        $this->assertSame(200, $paymentResponse->getStatusCode());

        $ipnBody = [
            "id" => "pay_1212",
            "status" => "ORDER_PAYED",
            "valuenet_gross" => 2.5,
            "valuenet" => 2.0,
            "valuepartner" => 1.5,
            "control" => $transactionId,
        ];
        $ipnBody["sign"] = hash(
            "sha256",
            $ipnBody["id"] .
                $ipnBody["status"] .
                $ipnBody["valuenet"] .
                $ipnBody["valuepartner"] .
                $ipnBody["control"] .
                ""
        );
        $response = $this->post("/api/ipn/direct-billing/{$paymentPlatform->getId()}", $ipnBody);
        $this->assertSame(200, $response->getStatusCode());
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertEqualsMoney(150, $freshUser->getWallet());
    }

    /** @test */
    public function charges_using_sms()
    {
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Pukawka::class);
        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Pukawka::MODULE_ID,
        ]);
        $this->settingsRepository->update([
            "sms_platform" => $paymentPlatform->getId(),
        ]);
        $user = $this->factory->user();
        $this->actingAs($user);

        $validationResponse = $this->post("/api/purchases", [
            "service_id" => ChargeWalletServiceModule::MODULE_ID,
            "payment_option" => make_charge_wallet_option(PaymentMethod::SMS(), $paymentPlatform),
            "sms_price" => 500,
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);
        $transactionId = $json["transaction_id"];

        $response = $this->post("/api/payment/{$transactionId}", [
            "method" => PaymentMethod::SMS(),
            "payment_platform_id" => $paymentPlatform->getId(),
            "sms_code" => "abc123",
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertEqualsMoney(326, $freshUser->getWallet());
    }
}
