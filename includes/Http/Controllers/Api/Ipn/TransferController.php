<?php
namespace App\Http\Controllers\Api\Ipn;

use App\Http\Responses\PlainResponse;
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
        TransferPaymentService $transferPaymentService
    ) {
        $paymentModule = $heart->getPaymentModuleByPlatformId($paymentPlatform);

        if (!($paymentModule instanceof SupportTransfer)) {
            return new PlainResponse(
                "Payment platform does not support transfer payment [${paymentPlatform}]."
            );
        }

        $finalizedPayment = $paymentModule->finalizeTransfer(
            $request->query->all(),
            $request->request->all()
        );

        $transferPaymentService->finalizePurchase($finalizedPayment);

        return new PlainResponse($finalizedPayment->getOutput());
    }

    /**
     * @deprecated
     */
    public function oldAction(
        Request $request,
        Heart $heart,
        TransferPaymentService $transferPaymentService
    ) {
        return $this->action(
            $request->query->get('service'),
            $request,
            $heart,
            $transferPaymentService
        );
    }
}
