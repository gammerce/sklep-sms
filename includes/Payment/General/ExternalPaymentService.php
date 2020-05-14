<?php
namespace App\Payment\General;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\LackOfValidPurchaseDataException;

class ExternalPaymentService
{
    /** @var PurchaseDataService */
    private $purchaseDataService;

    public function __construct(PurchaseDataService $purchaseDataService)
    {
        $this->purchaseDataService = $purchaseDataService;
    }

    /**
     * @param FinalizedPayment $finalizedPayment
     * @return Purchase
     * @throws LackOfValidPurchaseDataException
     */
    public function restorePurchase(FinalizedPayment $finalizedPayment)
    {
        $transactionId = $finalizedPayment->getTransactionId();
        $purchase = $this->purchaseDataService->restorePurchase($transactionId);

        if (!$purchase || $purchase->isAttempted()) {
            throw new LackOfValidPurchaseDataException();
        }

        $purchase->markAsAttempted();
        $this->purchaseDataService->storePurchase($purchase);

        return $purchase;
    }
}
