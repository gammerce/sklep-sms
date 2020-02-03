<?php
namespace App\Payment;

use App\Models\Price;

class PurchaseValidationService
{
    public function isPriceAvailable(Price $price, $serviceId, $serverId)
    {
        return $price->concernService($serviceId) && $price->concernServer($serverId);
    }
}
