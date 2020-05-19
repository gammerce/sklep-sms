<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResultType;
use App\Payment\General\PaymentService;
use App\Payment\General\PurchaseDataService;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class PaymentResource
{
    public function post(
        $transactionId,
        Request $request,
        PaymentService $paymentService,
        PurchaseDataService $purchaseDataService,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        $purchase = $purchaseDataService->restorePurchase($transactionId);
        if (!$purchase) {
            throw new EntityNotFoundException();
        }

        $method = as_payment_method($request->request->get("method"));
        $smsCode = trim($request->request->get("sms_code"));

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => $method,
            Purchase::PAYMENT_SMS_CODE => $smsCode,
        ]);

        try {
            $paymentResult = $paymentService->makePayment($purchase);
        } catch (PaymentProcessingException $e) {
            return new ApiResponse($e->getCode(), $e->getMessage(), false);
        }

        switch ($paymentResult->getType()) {
            case PaymentResultType::PURCHASED():
                $purchaseDataService->deletePurchase($purchase);

                return new ApiResponse("purchased", $lang->t("purchase_success"), true, [
                    "bsid" => $paymentResult->getData(),
                ]);

            case PaymentResultType::EXTERNAL():
                // Let's store changes made to purchase object
                // since it will be used later
                $purchaseDataService->storePurchase($purchase);

                return new ApiResponse("external", $lang->t("external_payment_prepared"), true, [
                    "data" => $paymentResult->getData(),
                ]);

            default:
                throw new UnexpectedValueException("Unexpected result type");
        }
    }
}
