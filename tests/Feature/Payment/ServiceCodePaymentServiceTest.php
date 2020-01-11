<?php
namespace Tests\Feature\Payment;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\ServiceCodePaymentService;
use App\Repositories\PaymentCodeRepository;
use App\Repositories\ServiceCodeRepository;
use App\System\Heart;
use Tests\Psr4\TestCases\TestCase;

class ServiceCodePaymentServiceTest extends TestCase
{
    /** @test */
    public function pay_using_service_code()
    {
        // given
        /** @var ServiceCodePaymentService $service */
        $service = $this->app->make(ServiceCodePaymentService::class);

        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        /** @var PaymentCodeRepository $paymentCodeRepository */
        $paymentCodeRepository = $this->app->make(PaymentCodeRepository::class);

        /** @var ServiceCodeRepository $serviceCodeRepository */
        $serviceCodeRepository = $this->app->make(ServiceCodeRepository::class);

        $serviceId = "vip";
        $serviceModule = $heart->getServiceModule($serviceId);

        $serviceCode = $serviceCodeRepository->create("ABC123", $serviceId);

        $purchase = new Purchase(new User());
        $purchase->setPayment([
            'service_code' => $serviceCode->getCode(),
        ]);
        $purchase->setOrder([
            'server' => 'blah',
        ]);
        $purchase->setTariff($heart->getTariff(2));
        $purchase->setService($serviceModule->service->getId());

        // when
        $paymentCodeId = $service->payWithServiceCode($purchase, $serviceModule);

        // then
        $this->assertInternalType("int", $paymentCodeId);
        $paymentCode = $paymentCodeRepository->get($paymentCodeId);
        $this->assertNotNull($paymentCode);
        $this->assertEquals($serviceCode->getCode(), $paymentCode->getCode());

        $freshServiceCode = $serviceCodeRepository->get($serviceCode->getId());
        $this->assertNull($freshServiceCode);
    }
}
