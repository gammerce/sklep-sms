<?php
namespace App\Payment\General;

class PaymentOption
{
    private PaymentMethod $paymentMethod;
    private ?int $paymentPlatformId;

    public function __construct(PaymentMethod $paymentMethod, $paymentPlatformId = null)
    {
        $this->paymentMethod = $paymentMethod;
        $this->paymentPlatformId = $paymentPlatformId;
    }

    /**
     * @return PaymentMethod
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @return int|null
     */
    public function getPaymentPlatformId()
    {
        return $this->paymentPlatformId;
    }

    /**
     * @param PaymentOption $paymentOption
     * @return bool
     */
    public function equal(PaymentOption $paymentOption)
    {
        return $this->getPaymentPlatformId() === $paymentOption->getPaymentPlatformId() &&
            $this->getPaymentMethod()->equals($paymentOption->getPaymentMethod());
    }
}
