<?php
namespace Tests\Feature\Http\Api\Ipn;

use App\Models\Purchase;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\ExtraFlags\ExtraFlagUserServiceRepository;
use App\ServiceModules\ExtraFlags\PlayerFlagRepository;
use App\Verification\PaymentModules\TPay;
use App\Verification\PaymentModules\Zabijaka;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseExtraFlagsTest extends HttpTestCase
{
    use PaymentModuleFactoryConcern;

    /** @var ExtraFlagUserServiceRepository */
    private $extraFlagUserServiceRepository;

    /** @var PlayerFlagRepository */
    private $playerFlagRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->extraFlagUserServiceRepository = $this->app->make(
            ExtraFlagUserServiceRepository::class
        );
        $this->playerFlagRepository = $this->app->make(PlayerFlagRepository::class);
    }

    /** @test */
    public function purchase_using_sms()
    {
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Zabijaka::class);
        $this->actingAs($this->factory->user());

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Zabijaka::MODULE_ID,
        ]);
        $server = $this->factory->server([
            "sms_platform_id" => $paymentPlatform->getId(),
        ]);
        $this->factory->serverService([
            "server_id" => $server->getId(),
            "service_id" => "vippro",
        ]);
        $price = $this->factory->price([
            "service_id" => "vippro",
            "server_id" => $server->getId(),
            "sms_price" => 500,
            "quantity" => 2,
        ]);

        $validationResponse = $this->post("/api/purchases", [
            "service_id" => "vippro",
            "method" => Purchase::METHOD_SMS,
            "type" => ExtraFlagType::TYPE_NICK,
            "auth_data" => "mama",
            "password" => "manq12a",
            "password_repeat" => "manq12a",
            "server_id" => $server->getId(),
            "quantity" => $price->getQuantity(),
            "email" => "a@a.pl",
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);
        $transactionId = $json["transaction_id"];

        $response = $this->post("/api/payment/{$transactionId}", [
            "method" => Purchase::METHOD_SMS,
            "sms_code" => "abc123",
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("purchased", $json["return_id"]);

        $expctedExpire = time() + 2 * 24 * 60 * 60;
        $userService = $this->extraFlagUserServiceRepository->findOrFail([
            "us.service_id" => "vippro",
        ]);
        $this->assertAlmostSameTimestamp($expctedExpire, $userService->getExpire());
        $this->assertSame($server->getId(), $userService->getServerId());
        $this->assertSame("vippro", $userService->getServiceId());
        $this->assertSame(ExtraFlagType::TYPE_NICK, $userService->getType());
        $this->assertSame("mama", $userService->getAuthData());
        $this->assertSame("manq12a", $userService->getPassword());

        $playerFlag = $this->playerFlagRepository->findOrFail([]);
        $this->assertSame($server->getId(), $playerFlag->getServerId());
        $this->assertSame(ExtraFlagType::TYPE_NICK, $playerFlag->getType());
        $this->assertSame("mama", $playerFlag->getAuthData());
        $this->assertSame("manq12a", $playerFlag->getPassword());
        $this->assertAlmostSameTimestamp($expctedExpire, $playerFlag->getFlag("b"));
        $this->assertAlmostSameTimestamp($expctedExpire, $playerFlag->getFlag("t"));
        $this->assertAlmostSameTimestamp($expctedExpire, $playerFlag->getFlag("x"));
        $this->assertSame(0, $playerFlag->getFlag("z"));
    }

    /** @test */
    public function buy_service_forever_using_transfer()
    {
        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $server = $this->factory->server([
            "transfer_platform_id" => $paymentPlatform->getId(),
        ]);
        $this->factory->serverService([
            "server_id" => $server->getId(),
            "service_id" => "vippro",
        ]);
        $this->factory->price([
            "service_id" => "vippro",
            "server_id" => $server->getId(),
            "transfer_price" => 3999,
            "quantity" => null,
        ]);

        $validationResponse = $this->post("/api/purchases", [
            "service_id" => "vippro",
            "method" => Purchase::METHOD_TRANSFER,
            "type" => ExtraFlagType::TYPE_NICK,
            "auth_data" => "mama",
            "password" => "manq12a",
            "password_repeat" => "manq12a",
            "server_id" => $server->getId(),
            "quantity" => -1,
            "email" => "a@a.pl",
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);
        $transactionId = $json["transaction_id"];

        $response = $this->post("/api/payment/{$transactionId}", [
            "method" => Purchase::METHOD_TRANSFER,
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("external", $json["return_id"]);

        $response = $this->post("/api/ipn/transfer/{$paymentPlatform->getId()}", [
            "tr_id" => 1,
            "tr_amount" => "39.99",
            "tr_crc" => $json["data"]["crc"],
            "id" => 1,
            "md5sum" => md5(
                array_get($paymentPlatform->getData(), "account_id") .
                    "1" .
                    "39.99" .
                    $json["data"]["crc"] .
                    ""
            ),
            "tr_status" => "TRUE",
            "tr_error" => "none",
        ]);
        $this->assertSame(200, $response->getStatusCode());

        $userService = $this->extraFlagUserServiceRepository->findOrFail([
            "us.service_id" => "vippro",
        ]);
        $this->assertSame(-1, $userService->getExpire());

        $playerFlag = $this->playerFlagRepository->findOrFail([]);
        $this->assertSame(-1, $playerFlag->getFlag("b"));
        $this->assertSame(-1, $playerFlag->getFlag("t"));
        $this->assertSame(-1, $playerFlag->getFlag("x"));
    }
}
