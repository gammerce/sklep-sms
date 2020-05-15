<?php
namespace App\Payment\Interfaces;

use App\Exceptions\ValidationException;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\ServiceModules\Interfaces\IServicePurchase;

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
     * @return PaymentResult
     * @throws PaymentProcessingException
     * @throws ValidationException
     */
    public function pay(Purchase $purchase, IServicePurchase $serviceModule);
}
