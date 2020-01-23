<?php
namespace Tests\Feature\Payment;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\TransferPaymentService;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\System\Heart;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\PaymentModules\Transferuj;
use Tests\Psr4\TestCases\TestCase;

class TransferPaymentServiceTest extends TestCase
{
    /** @test */
    public function pays_with_transfer()
    {
        // given
        /** @var TransferPaymentService $transferPaymentService */
        $transferPaymentService = $this->app->make(TransferPaymentService::class);

        /** @var PaymentTransferRepository $paymentTransferRepository */
        $paymentTransferRepository = $this->app->make(PaymentTransferRepository::class);

        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Transferuj::MODULE_ID,
        ]);

        /** @var SupportTransfer $paymentModule */
        $paymentModule = $heart->getPaymentModule($paymentPlatform);

        $serviceId = "vip";
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
        $purchase->setPrice($price);
        $purchase->setService($serviceModule->service->getId());
        $purchase->setDesc("Description");

        // when
        $payResult = $transferPaymentService->payWithTransfer($paymentModule, $purchase);
        $transferFinalize = $paymentModule->finalizeTransfer(
            [],
            [
                'tr_id' => "abc",
                'tr_amount' => $payResult["data"]["data"]["kwota"],
                'tr_crc' => $payResult["data"]["data"]["crc"],
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
