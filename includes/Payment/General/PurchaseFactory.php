<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Models\User;
use App\System\Settings;

class PurchaseFactory
{
    /** @var Settings */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param User $user
     * @return Purchase
     */
    public function create(User $user)
    {
        $purchase = new Purchase($user);

        if ($user->getEmail()) {
            $purchase->setEmail($user->getEmail());
        }

        $paymentSelect = $purchase->getPaymentSelect();

        if ($this->settings->getSmsPlatformId()) {
            $paymentSelect->setSmsPaymentPlatform($this->settings->getSmsPlatformId());
        }

        if ($this->settings->getDirectBillingPlatformId()) {
            $paymentSelect->setDirectBillingPaymentPlatform(
                $this->settings->getDirectBillingPlatformId()
            );
        }

        $paymentSelect->setTransferPaymentPlatforms($this->settings->getTransferPlatformIds());

        return $purchase;
    }
}
