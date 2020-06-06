<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Payment\General\PurchaseDataService;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Services\UserServiceAccessService;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PurchaseCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings,
        ServiceModuleManager $serviceModuleManager,
        PurchaseDataService $purchaseDataService,
        UserServiceAccessService $userServiceAccessService
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $serviceId = $request->request->get("service_id");
        $serviceModule = $serviceModuleManager->get($serviceId);

        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            throw new InvalidServiceModuleException();
        }

        if (!$userServiceAccessService->canUserUseService($serviceModule->service, $user)) {
            return new ApiResponse("no_permission", $lang->t("service_no_permission"), 0);
        }

        $purchase = (new Purchase($user))->setServiceId($serviceModule->service->getId());

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

        // TODO Set available payment platforms

        $serviceModule->purchaseFormValidate($purchase, $request->request->all());
        $purchaseDataService->storePurchase($purchase);

        return new ApiResponse("ok", $lang->t("purchase_form_validated"), true, [
            "transaction_id" => $purchase->getId(),
        ]);
    }
}
