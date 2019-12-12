<?php
namespace App\Http\Controllers\Api;

use App\Payment\PaymentService;
use App\System\Heart;
use App\Models\Purchase;
use App\Http\Responses\ApiResponse;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentResource
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Settings $settings,
        Heart $heart,
        PaymentService $paymentService
    ) {
        $lang = $translationManager->user();

        if (!$this->isCorrectlySigned($request, $settings['random_key'])) {
            return new ApiResponse("wrong_sign", $lang->translate('wrong_sign'), 0);
        }

        /** @var Purchase $purchase */
        $purchase = unserialize(base64_decode($request->request->get('purchase_data')));

        // Fix: Refresh data again to avoid bugs linked with user wallet
        $purchase->user = $heart->getUser($purchase->user->getUid());

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
