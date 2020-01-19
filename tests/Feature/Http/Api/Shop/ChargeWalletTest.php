<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Models\Purchase;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\Verification\PaymentModules\Microsms;
use App\Verification\PaymentModules\Pukawka;
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
        $this->makeVerifySmsSuccessful(Microsms::class);
        $paymentModule = $this->factory->paymentPlatform([
            'module' => Microsms::MODULE_ID,
        ]);
        $this->settingsRepository->update([
            'transfer_platform' => $paymentModule->getId(),
        ]);
        $this->actingAs($this->factory->user());

        $validationResponse = $this->post('/api/purchase/validation', [
            'service' => ChargeWalletServiceModule::MODULE_ID,
            'method' => Purchase::METHOD_TRANSFER,
            'transfer_price' => 600,
        ]);
        $this->assertSame(200, $validationResponse->getStatusCode());
        $json = $this->decodeJsonResponse($validationResponse);

        $response = $this->post('/api/payment', [
            'method' => Purchase::METHOD_TRANSFER,
            'purchase_sign' => $json["data"]["sign"],
            'purchase_data' => $json["data"]["data"],
        ]);
        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function charges_using_sms()
    {
        $this->makeVerifySmsSuccessful(Pukawka::class);
        $paymentModule = $this->factory->paymentPlatform([
            'module' => Pukawka::MODULE_ID,
        ]);
        $this->settingsRepository->update([
            'sms_platform' => $paymentModule->getId(),
        ]);
        $user = $this->factory->user();
        $this->actingAs($user);

        $validationResponse = $this->post('/api/purchase/validation', [
            'service' => ChargeWalletServiceModule::MODULE_ID,
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
        $this->assertSame(250, $freshUser->getWallet());
    }
}
