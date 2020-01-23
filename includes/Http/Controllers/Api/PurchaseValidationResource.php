<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Payment\PurchaseSerializer;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PurchaseValidationResource
{
    public function post(
        Request $request,
        Heart $heart,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings,
        PurchaseSerializer $purchaseSerializer
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $serviceId = $request->request->get('service_id');
        $serviceModule = $heart->getServiceModule($serviceId);

        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        // User does not belong to the group that allows to purchase that service
        if (!$heart->userCanUseService($user->getUid(), $serviceModule->service)) {
            return new ApiResponse("no_permission", $lang->t('service_no_permission'), 0);
        }

        $purchase = new Purchase($user);
        $purchase->setService($serviceModule->service->getId());

        if ($user->getEmail()) {
            $purchase->setEmail($user->getEmail());
        }

        if ($settings->getSmsPlatformId()) {
            $purchase->setPayment([
                Purchase::PAYMENT_SMS_PLATFORM => $settings->getSmsPlatformId(),
            ]);
        }

        if ($settings->getTransferPlatformId()) {
            $purchase->setPayment([
                Purchase::PAYMENT_TRANSFER_PLATFORM => $settings->getTransferPlatformId(),
            ]);
        }

        $returnData = $serviceModule->purchaseFormValidate($purchase, $request->request->all());

        if ($returnData['status'] == "warnings") {
            $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
        } else {
            $purchaseEncoded = $purchaseSerializer->serializeAndEncode($purchase);
            $returnData['data'] = [
                'length' => 8000,
                'data' => $purchaseEncoded,
                'sign' => md5($purchaseEncoded . $settings->getSecret()),
            ];
        }

        return new ApiResponse(
            $returnData['status'],
            $returnData['text'],
            $returnData['positive'],
            $returnData['data']
        );
    }
}
