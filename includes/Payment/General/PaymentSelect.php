<?php
namespace App\Payment\General;

class PaymentSelect
{
    private ?int $smsPaymentPlatform = null;
    private ?int $directBillingPaymentPlatform = null;
    private ?PaymentOption $allowedPaymentOption = null;

    /** @var int[] */
    private array $transferPaymentPlatforms = [];

    /** @var PaymentMethod[] */
    private array $disallowedPaymentMethods = [];

    /**
     * @return PaymentOption[]
     */
    public function all()
    {
        $output = [];

        if ($this->smsPaymentPlatform) {
            $output[] = new PaymentOption(PaymentMethod::SMS(), $this->smsPaymentPlatform);
        }

        if ($this->directBillingPaymentPlatform) {
            $output[] = new PaymentOption(
                PaymentMethod::DIRECT_BILLING(),
                $this->directBillingPaymentPlatform
            );
        }

        foreach ($this->transferPaymentPlatforms as $transferPaymentPlatform) {
            $output[] = new PaymentOption(PaymentMethod::TRANSFER(), $transferPaymentPlatform);
        }

        $output[] = new PaymentOption(PaymentMethod::WALLET());

        return collect($output)
            ->filter(
                fn(PaymentOption $paymentOption) => $this->allowedPaymentOption === null ||
                    $paymentOption->equal($this->allowedPaymentOption)
            )
            ->filter(function (PaymentOption $paymentOption) {
                foreach ($this->disallowedPaymentMethods as $disallowedPaymentMethod) {
                    if ($disallowedPaymentMethod->equals($paymentOption->getPaymentMethod())) {
                        return false;
                    }
                }

                return true;
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
     * @param PaymentOption $paymentOption
     */
    public function allowPaymentOption(PaymentOption $paymentOption)
    {
        $this->allowedPaymentOption = $paymentOption;
    }

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function disallowPaymentMethod(PaymentMethod $paymentMethod)
    {
        $this->disallowedPaymentMethods[] = $paymentMethod;
    }

    /**
     * @param PaymentOption $paymentOption
     * @return bool
     */
    public function contains(PaymentOption $paymentOption)
    {
        foreach ($this->all() as $item) {
            if ($paymentOption->equal($item)) {
                return true;
            }
        }

        return false;
    }
}
