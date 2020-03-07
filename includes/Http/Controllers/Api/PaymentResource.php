<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Payment\General\PaymentService;
use App\Payment\General\PurchaseSerializer;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentResource
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Settings $settings,
        PaymentService $paymentService,
        PurchaseSerializer $purchaseSerializer
    ) {
        $lang = $translationManager->user();

        if (!$this->isCorrectlySigned($request, $settings->getSecret())) {
            return new ApiResponse("wrong_sign", $lang->t('wrong_sign'), 0);
        }

        $purchase = $purchaseSerializer->deserializeAndDecode(
            $request->request->get('purchase_data')
        );

        // Add payment details
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => $request->request->get('method'),
            Purchase::PAYMENT_SMS_CODE => trim($request->request->get('sms_code')),
            Purchase::PAYMENT_SERVICE_CODE => trim($request->request->get('service_code')),
        ]);

        $paymentResult = $paymentService->makePayment($purchase);

        return new ApiResponse(
            $paymentResult->getStatus(),
            $paymentResult->getText(),
            $paymentResult->isPositive(),
            $paymentResult->getData()
        );
    }

    private function isCorrectlySigned(Request $request, $secret)
    {
        $sign = $request->request->get('purchase_sign');
        $purchase = $request->request->get("purchase_data");

        $calculatedSign = md5($purchase . $secret);

        return $sign === $calculatedSign;
    }
}
