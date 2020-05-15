<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Payment\General\PaymentService;
use App\Payment\General\PurchaseDataService;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentResource
{
    public function post(
        $transactionId,
        Request $request,
        PaymentService $paymentService,
        PurchaseDataService $purchaseDataService
    ) {
        $purchase = $purchaseDataService->restorePurchase($transactionId);

        if (!$purchase) {
            throw new EntityNotFoundException();
        }

        $method = $request->request->get("method");
        $smsCode = trim($request->request->get("sms_code"));

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => $method,
            Purchase::PAYMENT_SMS_CODE => $smsCode,
        ]);

        $paymentResult = $paymentService->makePayment($purchase);
        $paymentStatus = $paymentResult->getStatus();

        if ($paymentStatus === "purchased") {
            $purchaseDataService->deletePurchase($purchase);
        } elseif ($paymentStatus === "external") {
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
