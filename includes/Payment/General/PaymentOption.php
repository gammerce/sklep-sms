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

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getPaymentPlatformId(): ?int
    {
        return $this->paymentPlatformId;
    }

    public function equal(PaymentOption $paymentOption): bool
    {
        return $this->getPaymentPlatformId() === $paymentOption->getPaymentPlatformId() &&
            $this->getPaymentMethod()->equals($paymentOption->getPaymentMethod());
    }
}
