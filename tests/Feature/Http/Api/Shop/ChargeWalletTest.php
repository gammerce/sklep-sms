<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Models\Purchase;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\TPay;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class ChargeWalletTest extends HttpTestCase
{
    use PaymentModuleFactoryConcern;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->mockPaymentModuleFactory();
        $this->settingsRepository = $this->app->make(SettingsRepository::class);
        $this->userRepository = $this->app->make(UserRepository::class);
    }

    /** @test */
    public function charges_using_transfer()
    {
        $this->makeVerifySmsSuccessful(TPay::class);
        $paymentPlatform = $this->factory->paymentPlatform([
            'module' => TPay::MODULE_ID,
        ]);
        $this->settingsRepository->update([
            'transfer_platform' => $paymentPlatform->getId(),
        ]);
        $user = $this->factory->user();
        $this->actingAs($user);

        $validationResponse = $this->post('/api/purchase/validation', [
            'service_id' => ChargeWalletServiceModule::MODULE_ID,
            'method' => Purchase::METHOD_TRANSFER,
            'transfer_price' => 2.5,
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);

        $paymentResponse = $this->post('/api/payment', [
            'method' => Purchase::METHOD_TRANSFER,
            'purchase_sign' => $json["sign"],
            'purchase_data' => $json["data"],
        ]);
        $this->assertSame(200, $paymentResponse->getStatusCode());
        $json = $this->decodeJsonResponse($paymentResponse);

        $response = $this->post("/api/ipn/transfer/{$paymentPlatform->getId()}", [
            "tr_id" => 1,
            "tr_amount" => 1,
            "tr_crc" => $json["data"]["crc"],
            "id" => 1,
            "test_mode" => 1,
            "md5sum" => md5(
                array_get($paymentPlatform->getData(), "account_id") .
                    "1" .
                    "1.00" .
                    $json["data"]["crc"] .
                    ''
            ),
            "tr_status" => "TRUE",
            "tr_error" => "none",
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $freshUser = $this->userRepository->get($user->getUid());
        $this->assertSame(250, $freshUser->getWallet());
    }

    /** @test */
    public function charges_using_sms()
    {
        $this->makeVerifySmsSuccessful(Pukawka::class);
        $paymentPlatform = $this->factory->paymentPlatform([
            'module' => Pukawka::MODULE_ID,
        ]);
        $this->settingsRepository->update([
            'sms_platform' => $paymentPlatform->getId(),
        ]);
        $user = $this->factory->user();
        $this->actingAs($user);

        $validationResponse = $this->post('/api/purchase/validation', [
            'service_id' => ChargeWalletServiceModule::MODULE_ID,
            'method' => Purchase::METHOD_SMS,
            'sms_price' => 500,
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);

        $response = $this->post('/api/payment', [
            'method' => Purchase::METHOD_SMS,
            'sms_code' => 'abc123',
            'purchase_sign' => $json["sign"],
            'purchase_data' => $json["data"],
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $freshUser = $this->userRepository->get($user->getUid());
        $this->assertSame(326, $freshUser->getWallet());
    }
}
