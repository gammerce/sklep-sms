<?php
namespace App\Payment\Interfaces;

use App\Exceptions\ValidationException;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\ServiceModules\Interfaces\IServicePurchase;

interface IPaymentMethod
{
    public function getPaymentDetails(
        Purchase $purchase,
        ?PaymentPlatform $paymentPlatform = null
    ): ?array;

    public function isAvailable(Purchase $purchase, ?PaymentPlatform $paymentPlatform = null): bool;

    /**
     * @param Purchase $purchase
     * @param IServicePurchase $serviceModule
     * @return PaymentResult
     * @throws PaymentProcessingException
     * @throws ValidationException
     */
    public function pay(Purchase $purchase, IServicePurchase $serviceModule): PaymentResult;
}
