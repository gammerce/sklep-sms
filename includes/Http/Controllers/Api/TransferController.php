<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\Loggers\DatabaseLogger;
use App\Payment\Transfer\TransferPaymentService;
use App\System\Heart;
use App\Verification\Abstracts\SupportTransfer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransferController
{
    public function action(
        $transferPlatform,
        Request $request,
        Heart $heart,
        TransferPaymentService $transferPaymentService,
        DatabaseLogger $logger
    ) {
        $paymentModule = $heart->getPaymentModuleByPlatformId($transferPlatform);

        if (!($paymentModule instanceof SupportTransfer)) {
            return new PlainResponse(
                "Payment platform does not support transfer payments [${transferPlatform}]."
            );
        }

        $transferFinalize = $paymentModule->finalizeTransfer(
            $request->query->all(),
            $request->request->all()
        );

        if ($transferFinalize->getStatus() === false) {
            $logger->log(
                'payment_not_accepted',
                $transferFinalize->getOrderId(),
                $transferFinalize->getAmount(),
                $transferFinalize->getTransferService()
            );
        } else {
            $transferPaymentService->transferFinalize($transferFinalize);
        }

        return new Response($transferFinalize->getOutput(), 200, [
            'Content-type' => 'text/plain; charset="UTF-8"',
        ]);
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
