<?php
namespace App\Payment\Interfaces;

use App\Models\Purchase;
use App\ServiceModules\ServiceModule;

interface IPaymentMethod
{
    /**
     * @param Purchase $purchase
     * @return string
     */
    public function render(Purchase $purchase);

    /**
     * @param Purchase $purchase
     * @return bool
     */
    public function isAvailable(Purchase $purchase);

    /**
     * @param Purchase $purchase
     * @param ServiceModule $serviceModule
     * @return array
     */
    public function pay(Purchase $purchase, ServiceModule $serviceModule);
}
