<?php
namespace Tests\Feature\Payment;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\ServiceCode\ServiceCodePaymentService;
use App\Repositories\PaymentCodeRepository;
use App\Repositories\ServiceCodeRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagUserServiceRepository;
use Tests\Psr4\TestCases\TestCase;

class ServiceCodePaymentServiceTest extends TestCase
{
    /** @var ServiceCodePaymentService */
    private $serviceCodePaymentService;

    /** @var PaymentCodeRepository */
    private $paymentCodeRepository;

    /** @var ServiceCodeRepository */
    private $serviceCodeRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->serviceCodePaymentService = $this->app->make(ServiceCodePaymentService::class);
        $this->paymentCodeRepository = $this->app->make(PaymentCodeRepository::class);
        $this->serviceCodeRepository = $this->app->make(ServiceCodeRepository::class);
    }

    /** @test */
    public function pay_using_service_code()
    {
        // given
        $serviceId = "vip";
        $price = $this->factory->price([
            "quantity" => 40,
            "service_id" => $serviceId,
        ]);
        $serviceCode = $this->factory->serviceCode([
            "code" => "ABC123",
            "service_id" => $serviceId,
            "quantity" => 40,
        ]);

        $purchase = new Purchase(new User());
        $purchase->setPayment([
            Purchase::PAYMENT_SERVICE_CODE => $serviceCode->getCode(),
        ]);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => "blah",
        ]);
        $purchase->setUsingPrice($price);
        $purchase->setServiceId($serviceId);

        // when
        $paymentCodeId = $this->serviceCodePaymentService->payWithServiceCode($purchase);

        // then
        $this->assertInternalType("int", $paymentCodeId);
        $paymentCode = $this->paymentCodeRepository->get($paymentCodeId);
        $this->assertNotNull($paymentCode);
        $this->assertEquals($serviceCode->getCode(), $paymentCode->getCode());

        $freshServiceCode = $this->serviceCodeRepository->get($serviceCode->getId());
        $this->assertNull($freshServiceCode);
    }

    /** @test */
    public function purchase_product_forever()
    {
        // given
        $serviceId = "vip";
        $price = $this->factory->price([
            "quantity" => null,
            "service_id" => $serviceId,
        ]);
        $serviceCode = $this->factory->serviceCode([
            "code" => "ABC123",
            "service_id" => $serviceId,
            "quantity" => null,
        ]);

        $purchase = new Purchase(new User());
        $purchase->setPayment([
            Purchase::PAYMENT_SERVICE_CODE => $serviceCode->getCode(),
        ]);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => "blah",
        ]);
        $purchase->setUsingPrice($price);
        $purchase->setServiceId($serviceId);

        // when
        $paymentCodeId = $this->serviceCodePaymentService->payWithServiceCode($purchase);

        // then
        $this->assertInternalType("int", $paymentCodeId);
        $paymentCode = $this->paymentCodeRepository->get($paymentCodeId);
        $this->assertNotNull($paymentCode);
        $this->assertEquals($serviceCode->getCode(), $paymentCode->getCode());

        $freshServiceCode = $this->serviceCodeRepository->get($serviceCode->getId());
        $this->assertNull($freshServiceCode);
    }
}
