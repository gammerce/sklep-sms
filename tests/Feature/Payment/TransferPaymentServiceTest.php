<?php
namespace Tests\Feature\Payment;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\Transfer\TransferPaymentMethod;
use App\Payment\Transfer\TransferPaymentService;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\ServiceModule;
use App\System\Heart;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\PaymentModules\TPay;
use App\View\ServiceModuleManager;
use Tests\Psr4\TestCases\TestCase;

class TransferPaymentServiceTest extends TestCase
{
    /** @test */
    public function pays_with_transfer()
    {
        // given
        /** @var TransferPaymentService $transferPaymentService */
        $transferPaymentService = $this->app->make(TransferPaymentService::class);

        /** @var TransferPaymentMethod $transferPaymentMethod */
        $transferPaymentMethod = $this->app->make(TransferPaymentMethod::class);

        /** @var PaymentTransferRepository $paymentTransferRepository */
        $paymentTransferRepository = $this->app->make(PaymentTransferRepository::class);

        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        /** @var ServiceModuleManager $serviceModuleManager */
        $serviceModuleManager = $this->app->make(ServiceModuleManager::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);

        /** @var SupportTransfer $paymentModule */
        $paymentModule = $heart->getPaymentModule($paymentPlatform);

        $serviceId = "vip";
        /** @var IServicePurchase|ServiceModule $serviceModule */
        $serviceModule = $serviceModuleManager->get($serviceId);
        $server = $this->factory->server();
        $price = $this->factory->price([
            'service_id' => $serviceId,
            'server_id' => $server->getId(),
            'transfer_price' => 4080,
        ]);

        $purchase = new Purchase(new User());
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $server->getId(),
            'type' => ExtraFlagType::TYPE_SID,
        ]);
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_TRANSFER,
            Purchase::PAYMENT_PLATFORM_TRANSFER => $paymentPlatform->getId(),
        ]);
        $purchase->setUsingPrice($price);
        $purchase->setServiceId($serviceModule->service->getId());
        $purchase->setDesc("Description");

        // when
        $payResult = $transferPaymentMethod->pay($purchase, $serviceModule);
        $finalizedPayment = $paymentModule->finalizeTransfer(
            [],
            [
                'tr_id' => "abc",
                'tr_amount' => $payResult->getDatum("data")["kwota"],
                'tr_crc' => $payResult->getDatum("data")["crc"],
                'id' => "tpay",
                'md5sum' => "xyz",
            ]
        );
        $finalizedPayment->setStatus(true); // Mark as if checking md5sum was correct
        $transferPaymentService->finalizePurchase($purchase, $finalizedPayment);

        // then
        $paymentTransfer = $paymentTransferRepository->get($finalizedPayment->getOrderId());
        $this->assertNotNull($paymentTransfer);
        $this->assertEquals(4080, $paymentTransfer->getIncome());
    }
}
