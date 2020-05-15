<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Services\TransactionService;
use App\Managers\ServiceModuleManager;
use App\Payment\General\PurchaseDataService;
use App\PromoCode\PromoCodeService;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransactionPromoCodeResource
{
    public function post(
        $transactionId,
        $promoCode,
        PromoCodeService $promoCodeService,
        TransactionService $transactionService,
        PurchaseDataService $purchaseDataService,
        ServiceModuleManager $serviceModuleManager,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();
        $purchase = $purchaseDataService->restorePurchase($transactionId);

        if (!$purchase) {
            throw new EntityNotFoundException();
        }

        $serviceModule = $serviceModuleManager->get($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePromoCode)) {
            throw new InvalidServiceModuleException();
        }

        $promoCodeModel = $promoCodeService->findApplicablePromoCode($promoCode, $purchase);
        if (!$promoCodeModel) {
            return new ErrorApiResponse($lang->t("invalid_promo_code"));
        }

        $purchase->setPromoCode($promoCodeModel);
        $purchaseDataService->storePurchase($purchase);

        return new JsonResponse($transactionService->getTransactionDetails($purchase));
    }

    public function delete(
        $transactionId,
        PurchaseDataService $purchaseDataService,
        TransactionService $transactionService
    ) {
        $purchase = $purchaseDataService->restorePurchase($transactionId);

        if (!$purchase) {
            throw new EntityNotFoundException();
        }

        $purchase->setPromoCode(null);
        $purchaseDataService->storePurchase($purchase);

        return new JsonResponse($transactionService->getTransactionDetails($purchase));
    }
}
