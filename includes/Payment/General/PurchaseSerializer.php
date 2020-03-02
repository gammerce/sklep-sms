<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\System\Heart;

class PurchaseSerializer
{
    /** @var Heart */
    private $heart;

    public function __construct(Heart $heart)
    {
        $this->heart = $heart;
    }

    /**
     * @param Purchase $purchase
     * @return string
     */
    public function serialize(Purchase $purchase)
    {
        return serialize($purchase);
    }

    /**
     * @param Purchase $purchase
     * @return string
     */
    public function serializeAndEncode(Purchase $purchase)
    {
        return base64_encode(serialize($purchase));
    }

    /**
     * @param $content
     * @return Purchase|null
     */
    public function deserialize($content)
    {
        $purchase = unserialize($content);

        if (!($purchase instanceof Purchase)) {
            return null;
        }

        // Fix: Refresh user to avoid bugs linked with user wallet
        $purchase->user = $this->heart->getUser($purchase->user->getUid());

        return $purchase;
    }

    /**
     * @param $content
     * @return Purchase|null
     */
    public function deserializeAndDecode($content)
    {
        $purchase = unserialize(base64_decode($content));

        if (!($purchase instanceof Purchase)) {
            return null;
        }

        // Fix: Refresh user to avoid bugs linked with user wallet
        $purchase->user = $this->heart->getUser($purchase->user->getUid());

        return $purchase;
    }
}
