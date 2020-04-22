<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Payment\General\PurchaseDataService;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PurchaseCollection
{
    public function post(
        Request $request,
        Heart $heart,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings,
        PurchaseDataService $purchaseDataService
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $serviceId = $request->request->get('service_id');
        $serviceModule = $heart->getServiceModule($serviceId);

        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            throw new InvalidServiceModuleException();
        }

        // User does not belong to the group that allows to purchase that service
        if (!$heart->canUserUseService($user->getUid(), $serviceModule->service)) {
            return new ApiResponse("no_permission", $lang->t('service_no_permission'), 0);
        }

        $purchase = new Purchase($user);
        $purchase->setServiceId($serviceModule->service->getId());

        if ($user->getEmail()) {
            $purchase->setEmail($user->getEmail());
        }

        if ($settings->getSmsPlatformId()) {
            $purchase->setPayment([
                Purchase::PAYMENT_PLATFORM_SMS => $settings->getSmsPlatformId(),
            ]);
        }

        if ($settings->getTransferPlatformId()) {
            $purchase->setPayment([
                Purchase::PAYMENT_PLATFORM_TRANSFER => $settings->getTransferPlatformId(),
            ]);
        }

        if ($settings->getDirectBillingPlatformId()) {
            $purchase->setPayment([
                Purchase::PAYMENT_PLATFORM_DIRECT_BILLING => $settings->getDirectBillingPlatformId(),
            ]);
        }

        $serviceModule->purchaseFormValidate($purchase, $request->request->all());
        $transactionId = $purchaseDataService->storePurchase($purchase);

        return new ApiResponse("ok", $lang->t('purchase_form_validated'), true, [
            "transaction_id" => $transactionId,
        ]);
    }
}
