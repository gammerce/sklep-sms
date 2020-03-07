<?php
namespace App\Http\Controllers\Api\Ipn;

use App\Http\Responses\PlainResponse;
use App\Loggers\DatabaseLogger;
use App\Payment\DirectBilling\DirectBillingService;
use App\System\Heart;
use App\Verification\Abstracts\SupportDirectBilling;
use Symfony\Component\HttpFoundation\Request;

class DirectBillingController
{
    public function action(
        $paymentPlatform,
        Request $request,
        Heart $heart,
        DirectBillingService $directBillingService,
        DatabaseLogger $logger
    ) {
        $paymentModule = $heart->getPaymentModuleByPlatformId($paymentPlatform);

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return new PlainResponse(
                "Payment platform does not support direct billing payments [${paymentPlatform}]."
            );
        }

        $finalizedPayment = $paymentModule->finalizeDirectBilling(
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
            $directBillingService->finalizePurchase($finalizedPayment);
        }

        return new PlainResponse($finalizedPayment->getOutput());
    }
}
