<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Payment\PaymentService;
use App\Payment\PurchaseSerializer;
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
            'method' => $request->request->get('method'),
            'sms_code' => $request->request->get('sms_code'),
            'service_code' => $request->request->get('service_code'),
        ]);

        $returnPayment = $paymentService->makePayment($purchase);

        return new ApiResponse(
            $returnPayment['status'],
            $returnPayment['text'],
            $returnPayment['positive'],
            $returnPayment['data']
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
