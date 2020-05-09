<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Payment\General\PaymentService;
use App\Payment\General\PurchaseDataService;
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

        if (!$purchase || $purchase->isAttempted()) {
            throw new EntityNotFoundException();
        }

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => $request->request->get('method'),
            Purchase::PAYMENT_SMS_CODE => trim($request->request->get('sms_code')),
            Purchase::PAYMENT_SERVICE_CODE => trim($request->request->get('service_code')),
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
