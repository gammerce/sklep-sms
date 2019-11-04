<?php
namespace App\Controllers\Api;

use App\Heart;
use App\Models\Purchase;
use App\Responses\ApiResponse;
use App\Settings;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentResource
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Settings $settings,
        Heart $heart
    ) {
        $lang = $translationManager->user();

        if (
            !$request->request->has('purchase_sign') ||
            $request->request->get('purchase_sign') !=
                md5($request->request->get('purchase_data') . $settings['random_key'])
        ) {
            return new ApiResponse("wrong_sign", $lang->translate('wrong_sign'), 0);
        }

        /** @var Purchase $purchaseData */
        $purchaseData = unserialize(base64_decode($request->request->get('purchase_data')));

        // Fix: get user data again to avoid bugs linked with user wallet
        $purchaseData->user = $heart->getUser($purchaseData->user->getUid());

        // Add payment details
        $purchaseData->setPayment([
            'method' => $request->request->get('method'),
            'sms_code' => $request->request->get('sms_code'),
            'service_code' => $request->request->get('service_code'),
        ]);

        $returnPayment = make_payment($purchaseData);

        return new ApiResponse(
            $returnPayment['status'],
            $returnPayment['text'],
            $returnPayment['positive'],
            $returnPayment['data']
        );
    }
}
