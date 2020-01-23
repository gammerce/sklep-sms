<?php
namespace App\Payment;

use App\Models\Price;
use App\Models\Purchase;

class PurchaseValidationService
{
    public function isPriceAvailable(Price $price, Purchase $purchase)
    {
        return $price->concernService($purchase->getService()) &&
            $price->concernServer($purchase->getOrder(Purchase::ORDER_SERVER));
    }
}
