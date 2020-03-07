<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\Loggers\DatabaseLogger;
use App\Payment\Transfer\TransferPaymentService;
use App\System\Heart;
use App\Verification\Abstracts\SupportTransfer;
use Symfony\Component\HttpFoundation\Request;

class TransferController
{
    public function action(
        $paymentPlatform,
        Request $request,
        Heart $heart,
        TransferPaymentService $transferPaymentService,
        DatabaseLogger $logger
    ) {
        $paymentModule = $heart->getPaymentModuleByPlatformId($paymentPlatform);

        if (!($paymentModule instanceof SupportTransfer)) {
            return new PlainResponse(
                "Payment platform does not support transfer payments [${paymentPlatform}]."
            );
        }

        $finalizedPayment = $paymentModule->finalizeTransfer(
            $request->query->all(),
            $request->request->all()
        );

        if (!$finalizedPayment->getStatus()) {
            $logger->log(
                'payment_not_accepted',
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getAmount(),
                $finalizedPayment->getExternalServiceId()
            );
        } else {
            $transferPaymentService->finalizePurchase($finalizedPayment);
        }

        return new PlainResponse($finalizedPayment->getOutput());
    }

    /**
     * @deprecated
     */
    public function oldAction(
        Request $request,
        Heart $heart,
        TransferPaymentService $transferPaymentService,
        DatabaseLogger $logger
    ) {
        return $this->action(
            $request->query->get('service'),
            $request,
            $heart,
            $transferPaymentService,
            $logger
        );
    }
}
