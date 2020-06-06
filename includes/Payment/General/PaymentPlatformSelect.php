<?php
namespace App\Payment\General;

use App\Support\Collection;
use App\Support\Whenable;

class PaymentPlatformSelect
{
    use Whenable;

    /** @var int|null */
    private $smsPaymentPlatform;

    /** @var int[] */
    private $transferPaymentPlatforms = [];

    /** @var int|null */
    private $directBillingPaymentPlatform;

    /**
     * @return int[]
     */
    public function all()
    {
        return collect()
            ->extend($this->transferPaymentPlatforms)
            ->when(!!$this->smsPaymentPlatform, function (Collection $collection) {
                $collection->push($this->smsPaymentPlatform);
            })
            ->when(!!$this->directBillingPaymentPlatform, function (Collection $collection) {
                $collection->push($this->directBillingPaymentPlatform);
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
}
