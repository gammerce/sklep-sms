<?php
namespace App\Http\Controllers\Api\Ipn;

use App\Http\Responses\PlainResponse;
use App\Payment\DirectBilling\DirectBillingPaymentService;
use App\System\Heart;
use App\Verification\Abstracts\SupportDirectBilling;
use Symfony\Component\HttpFoundation\Request;

// TODO Add admin page with direct billing payments

class DirectBillingController
{
    public function action(
        $paymentPlatform,
        Request $request,
        Heart $heart,
        DirectBillingPaymentService $directBillingPaymentService
    ) {
        $paymentModule = $heart->getPaymentModuleByPlatformId($paymentPlatform);

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return new PlainResponse(
                "Payment platform does not support direct billing payment [${paymentPlatform}]."
            );
        }

        $finalizedPayment = $paymentModule->finalizeDirectBilling(
            $request->query->all(),
            $request->request->all()
        );
        $directBillingPaymentService->finalizePurchase($finalizedPayment);

        return new PlainResponse($finalizedPayment->getOutput());
    }
}
