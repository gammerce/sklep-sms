<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ErrorApiResponse;
use App\Managers\ServiceModuleManager;
use App\Payment\General\PaymentMethodFactory;
use App\Payment\General\PurchaseDataService;
use App\Payment\Interfaces\IPaymentMethod;
use App\PromoCode\PromoCodeService;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TransactionResource
{
    public function get(
        $transactionId,
        Request $request,
        PromoCodeService $promoCodeService,
        PurchaseDataService $purchaseDataService,
        ServiceModuleManager $serviceModuleManager,
        PaymentMethodFactory $paymentMethodFactory,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();
        $purchase = $purchaseDataService->restorePurchase($transactionId);

        if (!$purchase || $purchase->isAttempted()) {
            throw new EntityNotFoundException();
        }

        $serviceModule = $serviceModuleManager->get($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            throw new InvalidServiceModuleException();
        }

        if ($serviceModule instanceof IServicePromoCode) {
            $promoCode = trim($request->query->get("promo_code"));

            if ($promoCode) {
                $promoCodeModel = $promoCodeService->findApplicablePromoCode($purchase, $promoCode);

                if (!$promoCodeModel) {
                    return new ErrorApiResponse($lang->t("invalid_promo_code"));
                }

                $purchase->setPromoCode($promoCodeModel);
            }
        }

        $paymentMethods = collect($paymentMethodFactory->createAll())
            ->filter(function (IPaymentMethod $paymentMethod) use ($purchase) {
                return $paymentMethod->isAvailable($purchase);
            })
            ->mapWithKeys(function (IPaymentMethod $paymentMethod) use ($purchase) {
                return $paymentMethod->getPaymentDetails($purchase);
            })
            ->all();

        return new JsonResponse([
            "promo_code" => $serviceModule instanceof IServicePromoCode,
            "payment_methods" => $paymentMethods,
        ]);
    }
}
