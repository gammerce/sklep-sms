<?php
namespace Tests\Feature\Payment;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\DirectBilling\DirectBillingPaymentMethod;
use App\Payment\DirectBilling\DirectBillingPaymentService;
use App\Payment\Transfer\TransferPaymentMethod;
use App\Payment\Transfer\TransferPaymentService;
use App\Repositories\PaymentDirectBillingRepository;
use App\Repositories\PaymentTransferRepository;
use App\Requesting\Response;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\ServiceModule;
use App\System\Heart;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Mockery;
use Tests\Psr4\Concerns\RequesterConcern;
use Tests\Psr4\TestCases\TestCase;

class DirectBillingPaymentServiceTest extends TestCase
{
    use RequesterConcern;

    /** @test */
    public function pays_with_transfer()
    {
        // given
        $dataFileName = null;
        $this->mockRequester();
        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs(["https://simpay.pl/db/api", Mockery::any()])
            ->andReturnUsing(function ($url, $body) use (&$dataFileName) {
                $dataFileName = $body["control"];
                return new Response(
                    200,
                    json_encode([
                        "status" => "success",
                        "link" => "https://example.com",
                    ])
                );
            });

        /** @var DirectBillingPaymentService $directBillingPaymentService */
        $directBillingPaymentService = $this->app->make(DirectBillingPaymentService::class);

        /** @var DirectBillingPaymentMethod $directBillingPaymentMethod */
        $directBillingPaymentMethod = $this->app->make(DirectBillingPaymentMethod::class);

        /** @var PaymentDirectBillingRepository $paymentDirectBillingRepository */
        $paymentDirectBillingRepository = $this->app->make(PaymentDirectBillingRepository::class);

        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);

        /** @var SupportDirectBilling $paymentModule */
        $paymentModule = $heart->getPaymentModule($paymentPlatform);

        $serviceId = "vip";
        /** @var IServicePurchase|ServiceModule $serviceModule */
        $serviceModule = $heart->getServiceModule($serviceId);
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
                "control" => $dataFileName,
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
