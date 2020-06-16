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

    /** @var PaymentOption|null */
    private $allowedPaymentOption;

    /** @var PaymentMethod[] */
    private $disallowedPaymentMethods = [];

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
            ->filter(function (PaymentOption $paymentOption) {
                return $this->allowedPaymentOption === null ||
                    payment_option_equals($paymentOption, $this->allowedPaymentOption);
            })
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
            if (payment_option_equals($paymentOption, $item)) {
                return true;
            }
        }

        return false;
    }
}
