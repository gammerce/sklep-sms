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
    public function all(): array
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
     * @return self
     */
    public function setSmsPaymentPlatform($paymentPlatform): self
    {
        $this->smsPaymentPlatform = $paymentPlatform;
        return $this;
    }

    /**
     * @param int[] $paymentPlatforms
     * @return self
     */
    public function setTransferPaymentPlatforms(array $paymentPlatforms): self
    {
        $this->transferPaymentPlatforms = $paymentPlatforms;
        return $this;
    }

    /**
     * @param int $paymentPlatform
     * @return self
     */
    public function setDirectBillingPaymentPlatform($paymentPlatform): self
    {
        $this->directBillingPaymentPlatform = $paymentPlatform;
        return $this;
    }

    public function allowPaymentOption(PaymentOption $paymentOption): void
    {
        $this->allowedPaymentOption = $paymentOption;
    }

    public function disallowPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->disallowedPaymentMethods[] = $paymentMethod;
    }

    public function contains(PaymentOption $paymentOption): bool
    {
        foreach ($this->all() as $item) {
            if ($paymentOption->equal($item)) {
                return true;
            }
        }

        return false;
    }
}
