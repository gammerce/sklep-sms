<?php
namespace Tests\Feature\Payment;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\PaymentService;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\SmsCodeRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Verification\PaymentModules\Pukawka;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\TestCase;

class PaymentServiceTest extends TestCase
{
    use PaymentModuleFactoryConcern;

    /** @var PaymentService */
    private $paymentService;

    /** @var BoughtServiceRepository */
    private $boughtServiceRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Pukawka::class);

        $this->paymentService = $this->app->make(PaymentService::class);
        $this->boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);
    }

    /** @test */
    public function pays_with_sms()
    {
        // given
        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Pukawka::MODULE_ID,
        ]);

        $serviceId = "cod_exp_transfer";
        $server = $this->factory->server();
        $price = $this->factory->price([
            'service_id' => $serviceId,
            'server_id' => $server->getId(),
            'sms_price' => 100,
            'quantity' => 20,
        ]);

        $purchase = new Purchase(new User());
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $server->getId(),
            'type' => ExtraFlagType::TYPE_SID,
        ]);
        $purchase->setPrice($price);
        $purchase->setService($serviceId);
        $purchase->setPayment([
            Purchase::PAYMENT_PLATFORM_SMS => $paymentPlatform->getId(),
            Purchase::PAYMENT_SMS_CODE => "abcd1234",
            Purchase::PAYMENT_METHOD => Purchase::METHOD_SMS,
        ]);

        // when
        $payResult = $this->paymentService->makePayment($purchase);

        // then
        $this->assertSame("purchased", $payResult["status"]);
        $boughtService = $this->boughtServiceRepository->get($payResult["data"]["bsid"]);
        $this->assertNotNull($boughtService);
        $this->assertSame($server->getId(), $boughtService->getServerId());
        $this->assertSame($serviceId, $boughtService->getServiceId());
        $this->assertSame(0, $boughtService->getUid());
        $this->assertSame(Purchase::METHOD_SMS, $boughtService->getMethod());
        $this->assertEquals(20, $boughtService->getAmount());
        $this->assertSame('', $boughtService->getAuthData());
    }

    /** @test */
    public function pays_with_sms_code()
    {
        // given
        /** @var SmsCodeRepository $smsCodeRepository */
        $smsCodeRepository = $this->app->make(SmsCodeRepository::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Pukawka::MODULE_ID,
        ]);
        $smsCode = $smsCodeRepository->create("QWERTY", 200, false);
        $serviceId = "vip";
        $server = $this->factory->server();
        $price = $this->factory->price([
            'service_id' => $serviceId,
            'sms_price' => 200,
        ]);

        $purchase = new Purchase(new User());
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $server->getId(),
            'type' => ExtraFlagType::TYPE_SID,
        ]);
        $purchase->setPrice($price);
        $purchase->setService($serviceId);
        $purchase->setPayment([
            Purchase::PAYMENT_PLATFORM_SMS => $paymentPlatform->getId(),
            Purchase::PAYMENT_SMS_CODE => "QWERTY",
            Purchase::PAYMENT_METHOD => Purchase::METHOD_SMS,
        ]);

        // when
        $payResult = $this->paymentService->makePayment($purchase);

        // then
        $this->assertSame("purchased", $payResult["status"]);
        $boughtService = $this->boughtServiceRepository->get($payResult["data"]["bsid"]);
        $this->assertNotNull($boughtService);
        $this->assertNull($smsCodeRepository->get($smsCode->getId()));
    }

    /** @test */
    public function purchase_forever()
    {
        // given
        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Pukawka::MODULE_ID,
        ]);

        $serviceId = "vip";
        $server = $this->factory->server();
        $price = $this->factory->price([
            'service_id' => $serviceId,
            'sms_price' => 100,
            'quantity' => null,
        ]);

        $purchase = new Purchase(new User());
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $server->getId(),
            'type' => ExtraFlagType::TYPE_SID,
            'auth_data' => 'STEAM_1:0:22309350',
        ]);
        $purchase->setPrice($price);
        $purchase->setService($serviceId);
        $purchase->setPayment([
            Purchase::PAYMENT_PLATFORM_SMS => $paymentPlatform->getId(),
            Purchase::PAYMENT_SMS_CODE => "abcd1234",
            Purchase::PAYMENT_METHOD => Purchase::METHOD_SMS,
        ]);

        // when
        $payResult = $this->paymentService->makePayment($purchase);

        // then
        $this->assertSame("purchased", $payResult["status"]);
        $boughtService = $this->boughtServiceRepository->get($payResult["data"]["bsid"]);
        $this->assertNotNull($boughtService);
        $this->assertEquals(-1, $boughtService->getAmount());
        $this->assertSame('STEAM_1:0:22309350', $boughtService->getAuthData());
    }
}
