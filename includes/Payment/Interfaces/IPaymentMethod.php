<?php
namespace App\Payment\Interfaces;

use App\Models\Purchase;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Support\Result;

interface IPaymentMethod
{
    /**
     * @param Purchase $purchase
     * @return array
     */
    public function getPaymentDetails(Purchase $purchase);

    /**
     * @param Purchase $purchase
     * @return bool
     */
    public function isAvailable(Purchase $purchase);

    /**
     * @param Purchase $purchase
     * @param IServicePurchase $serviceModule
     * @return Result
     */
    public function pay(Purchase $purchase, IServicePurchase $serviceModule);
}
