<?php
namespace App\Payment\General;

class PaymentOption
{
    /** @var PaymentMethod */
    private $paymentMethod;

    /** @var int|null */
    private $paymentPlatformId;

    public function __construct(PaymentMethod $paymentMethod, $paymentPlatform = null)
    {
        $this->paymentMethod = $paymentMethod;
        $this->paymentPlatformId = $paymentPlatform;
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
}
