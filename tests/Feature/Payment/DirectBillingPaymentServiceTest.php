<?php
namespace Tests\Feature\Payment;

use App\Managers\PaymentModuleManager;
use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Models\User;
use App\Payment\DirectBilling\DirectBillingPaymentMethod;
use App\Payment\DirectBilling\DirectBillingPaymentService;
use App\Repositories\PaymentDirectBillingRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\ServiceModule;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Verification\PaymentModules\SimPay;
use Tests\Psr4\Concerns\SimPayConcern;
use Tests\Psr4\TestCases\TestCase;

class DirectBillingPaymentServiceTest extends TestCase
{
    use SimPayConcern;

    /** @test */
    public function pays_with_transfer()
    {
        // given
        $this->mockSimPayIpList();
        $this->mockSimPayApiSuccessResponse();

        /** @var DirectBillingPaymentService $directBillingPaymentService */
        $directBillingPaymentService = $this->app->make(DirectBillingPaymentService::class);

        /** @var DirectBillingPaymentMethod $directBillingPaymentMethod */
        $directBillingPaymentMethod = $this->app->make(DirectBillingPaymentMethod::class);

        /** @var PaymentDirectBillingRepository $paymentDirectBillingRepository */
        $paymentDirectBillingRepository = $this->app->make(PaymentDirectBillingRepository::class);

        /** @var PaymentModuleManager $paymentModuleManager */
        $paymentModuleManager = $this->app->make(PaymentModuleManager::class);

        /** @var ServiceModuleManager $serviceModuleManager */
        $serviceModuleManager = $this->app->make(ServiceModuleManager::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);

        /** @var SupportDirectBilling $paymentModule */
        $paymentModule = $paymentModuleManager->get($paymentPlatform);

        $serviceId = "vip";
        /** @var IServicePurchase|ServiceModule $serviceModule */
        $serviceModule = $serviceModuleManager->get($serviceId);
        $server = $this->factory->server();
        $price = $this->factory->price([
            "service_id" => $serviceId,
            "server_id" => $server->getId(),
            "direct_billing_price" => 190,
        ]);

        $purchase = new Purchase(new User());
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $server->getId(),
            "type" => ExtraFlagType::TYPE_SID,
        ]);
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_DIRECT_BILLING,
            Purchase::PAYMENT_PLATFORM_DIRECT_BILLING => $paymentPlatform->getId(),
        ]);
        $purchase->setUsingPrice($price);
        $purchase->setServiceId($serviceId);

        // when
        $directBillingPaymentMethod->pay($purchase, $serviceModule);
        $finalizedPayment = $paymentModule->finalizeDirectBilling(
            [],
            [
                "id" => "pay_1212",
                "status" => "ORDER_PAYED",
                "valuenet_gross" => 1.9,
                "valuenet" => 1.5,
                "valuepartner" => 1.2,
                "control" => $purchase->getId(),
                "sign" => "",
            ]
        );
        $finalizedPayment->setStatus(true);
        $directBillingPaymentService->finalizePurchase($purchase, $finalizedPayment);

        // then
        $payment = $paymentDirectBillingRepository->findByExternalId(
            $finalizedPayment->getOrderId()
        );
        $this->assertNotNull($payment);
        $this->assertSame(120, $payment->getIncome());
        $this->assertSame(190, $payment->getCost());
        $this->assertFalse($payment->isFree());
    }
}
