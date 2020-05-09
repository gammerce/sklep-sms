<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorApiResponse;
use App\Models\Purchase;
use App\Payment\General\PaymentService;
use App\Payment\General\PurchaseDataService;
use App\Repositories\PromoCodeRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentResource
{
    public function post(
        $transactionId,
        Request $request,
        PaymentService $paymentService,
        PurchaseDataService $purchaseDataService,
        PromoCodeRepository $promoCodeRepository,
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
            $promoCodeModel = $promoCodeRepository->get($promoCode);
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
            $purchaseDataService->deletePurchase($transactionId);
        }

        return new ApiResponse(
            $paymentResult->getStatus(),
            $paymentResult->getText(),
            $paymentResult->isPositive(),
            $paymentResult->getData()
        );
    }
}
