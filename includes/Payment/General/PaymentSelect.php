<?php
namespace App\Payment\General;

class PaymentSelect
{
    /** @var int|null */
    private $smsPaymentPlatform;

    /** @var int[] */
    private $transferPaymentPlatforms = [];

    /** @var int|null */
    private $directBillingPaymentPlatform;

    /** @var PaymentMethod|null */
    private $allowedPaymentMethod;

    /**
     * @return PaymentOption[]
     */
    public function all()
    {
        $output = [new PaymentOption(PaymentMethod::WALLET())];

        foreach ($this->transferPaymentPlatforms as $transferPaymentPlatform) {
            $output[] = new PaymentOption(PaymentMethod::TRANSFER(), $transferPaymentPlatform);
        }

        if ($this->smsPaymentPlatform) {
            $output[] = new PaymentOption(PaymentMethod::SMS(), $this->smsPaymentPlatform);
        }

        if ($this->directBillingPaymentPlatform) {
            $output[] = new PaymentOption(
                PaymentMethod::DIRECT_BILLING(),
                $this->directBillingPaymentPlatform
            );
        }

        return collect($output)
            ->filter(function (PaymentOption $paymentOption) {
                return $this->allowedPaymentMethod === null ||
                    $this->allowedPaymentMethod->equals($paymentOption->getPaymentMethod());
            })
            ->all();
    }

    /**
     * @param int $paymentPlatform
     * @return $this
     */
    public function setSmsPaymentPlatform($paymentPlatform)
    {
        $this->smsPaymentPlatform = $paymentPlatform;
        return $this;
    }

    /**
     * @param int[] $paymentPlatforms
     * @return $this
     */
    public function setTransferPaymentPlatforms(array $paymentPlatforms)
    {
        $this->transferPaymentPlatforms = $paymentPlatforms;
        return $this;
    }

    /**
     * @param int $paymentPlatform
     * @return $this
     */
    public function setDirectBillingPaymentPlatform($paymentPlatform)
    {
        $this->directBillingPaymentPlatform = $paymentPlatform;
        return $this;
    }

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function allowPaymentMethod(PaymentMethod $paymentMethod)
    {
        $this->allowedPaymentMethod = $paymentMethod;
    }
}
