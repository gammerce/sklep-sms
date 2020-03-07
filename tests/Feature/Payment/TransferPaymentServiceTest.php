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

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);

        /** @var SupportTransfer $paymentModule */
        $paymentModule = $heart->getPaymentModule($paymentPlatform);

        $serviceId = "vip";
        /** @var IServicePurchase|ServiceModule $serviceModule */
        $serviceModule = $heart->getServiceModule($serviceId);
        $server = $this->factory->server();
        $price = $this->factory->price([
            'service_id' => $serviceId,
            'server_id' => $server->getId(),
            'transfer_price' => 190,
        ]);

        $purchase = new Purchase(new User());
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $server->getId(),
            'type' => ExtraFlagType::TYPE_SID,
        ]);
        $purchase->setPayment([
            Purchase::PAYMENT_PLATFORM_TRANSFER => $paymentPlatform->getId(),
        ]);
        $purchase->setPrice($price);
        $purchase->setServiceId($serviceModule->service->getId());
        $purchase->setDesc("Description");

        // when
        $payResult = $transferPaymentMethod->pay($purchase, $serviceModule);
        $transferFinalize = $paymentModule->finalizeTransfer(
            [],
            [
                'tr_id' => "abc",
                'tr_amount' => $payResult->getDatum("data")["kwota"],
                'tr_crc' => $payResult->getDatum("data")["crc"],
                'id' => "tpay",
                'md5sum' => "xyz",
            ]
        );
        $transferFinalize->setStatus(true); // Mark as if checking md5sum was correct
        $result = $transferPaymentService->transferFinalize($transferFinalize);

        // then
        $this->assertTrue($result);
        $paymentTransfer = $paymentTransferRepository->get($transferFinalize->getOrderId());
        $this->assertNotNull($paymentTransfer);
        $this->assertEquals(190, $paymentTransfer->getIncome());
    }
}
