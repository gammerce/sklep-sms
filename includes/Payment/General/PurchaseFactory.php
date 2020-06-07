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

        $purchase
            ->getPaymentPlatformSelect()
            ->when($this->settings->getSmsPlatformId(), function (PaymentPlatformSelect $select) {
                $select->setSmsPaymentPlatform($this->settings->getSmsPlatformId());
            })
            ->when($this->settings->getDirectBillingPlatformId(), function (
                PaymentPlatformSelect $select
            ) {
                $select->setDirectBillingPaymentPlatform(
                    $this->settings->getDirectBillingPlatformId()
                );
            })
            ->when($this->settings->getTransferPlatformId(), function (
                PaymentPlatformSelect $select
            ) {
                $select->setTransferPaymentPlatforms([$this->settings->getTransferPlatformId()]);
            });

        return $purchase;
    }
}
