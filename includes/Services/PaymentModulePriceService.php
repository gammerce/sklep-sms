<?php
namespace App\Services;

use App\Models\Price;
use App\Verification\Abstracts\PaymentModule;

class PaymentModulePriceService
{
    public function isPriceAvailable(Price $price, PaymentModule $paymentModule)
    {
        // TODO Implement
        return true;
    }
}
