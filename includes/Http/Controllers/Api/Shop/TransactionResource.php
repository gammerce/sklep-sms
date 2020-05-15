<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Http\Services\TransactionService;
use App\Payment\General\PurchaseDataService;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransactionResource
{
    public function get(
        $transactionId,
        PurchaseDataService $purchaseDataService,
        TransactionService $transactionService
    ) {
        $purchase = $purchaseDataService->restorePurchase($transactionId);

        if (!$purchase) {
            throw new EntityNotFoundException();
        }

        return new JsonResponse($transactionService->getTransactionDetails($purchase));
    }
}
