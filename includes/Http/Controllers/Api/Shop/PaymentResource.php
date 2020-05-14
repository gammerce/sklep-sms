<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorApiResponse;
use App\Models\Purchase;
use App\Payment\General\PaymentService;
use App\Payment\General\PurchaseDataService;
use App\PromoCode\PromoCodeService;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentResource
{
    public function post(
        $transactionId,
        Request $request,
        PaymentService $paymentService,
        PromoCodeService $promoCodeService,
        PurchaseDataService $purchaseDataService,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();
        $purchase = $purchaseDataService->restorePurchase($transactionId);

        if (!$purchase || $purchase->isAttempted()) {
            throw new EntityNotFoundException();
        }

        $method = $request->request->get("method");
        $smsCode = trim($request->request->get("sms_code"));
        $promoCode = trim($request->request->get("promo_code"));

        if (strlen($promoCode)) {
            $promoCodeModel = $promoCodeService->findApplicablePromoCode($purchase, $promoCode);

            if (!$promoCodeModel) {
                return new ErrorApiResponse($lang->t("invalid_promo_code"));
            }

            $purchase->setPromoCode($promoCodeModel);
        }

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => $method,
            Purchase::PAYMENT_SMS_CODE => $smsCode,
        ]);

        $paymentResult = $paymentService->makePayment($purchase);

        if ($paymentResult->getStatus() === "purchased") {
            $purchaseDataService->deletePurchase($purchase);
        } elseif ($paymentResult->getStatus() === "external") {
            // Let's store changes made to purchase object
            // since it will be used later
            $purchaseDataService->storePurchase($purchase);
        }

        return new ApiResponse(
            $paymentResult->getStatus(),
            $paymentResult->getText(),
            $paymentResult->isPositive(),
            $paymentResult->getData()
        );
    }
}
