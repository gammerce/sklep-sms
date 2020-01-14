<?php
namespace App\Services;

use App\Verification\Abstracts\PaymentModule;

class SmsPriceService
{
    public function isPriceAvailable($smsPrice, PaymentModule $paymentModule)
    {
        // TODO Implement
        return true;
    }

    public function getNumber($smsPrice, PaymentModule $paymentModule)
    {
        // TODO Implement
        return null;
    }

    public function getProvision($smsPrice, PaymentModule $paymentModule = null)
    {
        // TODO Implement
        return (int) ($smsPrice / 2);
    }
}
