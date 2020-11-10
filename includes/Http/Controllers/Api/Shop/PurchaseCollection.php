<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\Managers\ServiceModuleManager;
use App\Payment\General\PurchaseDataService;
use App\Payment\General\PurchaseFactory;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Services\UserServiceAccessService;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PurchaseCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        ServiceModuleManager $serviceModuleManager,
        PurchaseDataService $purchaseDataService,
        UserServiceAccessService $userServiceAccessService,
        PurchaseFactory $purchaseFactory
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

        $purchase = $purchaseFactory
            ->create($user, get_platform($request))
            ->setServiceId($serviceModule->service->getId())
            ->setDescription(
                $lang->t("payment_for_service", $serviceModule->service->getNameI18n())
            );

        $serviceModule->purchaseFormValidate($purchase, $request->request->all());
        $purchaseDataService->storePurchase($purchase);

        return new ApiResponse("ok", $lang->t("purchase_form_validated"), true, [
            "transaction_id" => $purchase->getId(),
        ]);
    }
}
