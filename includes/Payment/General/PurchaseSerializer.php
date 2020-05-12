<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Models\User;
use App\Repositories\UserRepository;

class PurchaseSerializer
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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
     * @param $content
     * @return Purchase|null
     */
    public function deserialize($content)
    {
        return $this->enhancePurchase(unserialize($content));
    }

    /**
     * @param mixed $purchase
     * @return Purchase|null
     */
    private function enhancePurchase($purchase)
    {
        if ($purchase instanceof Purchase) {
            // Fix: Refresh user to avoid bugs linked with user wallet
            $purchase->user = $this->userRepository->get($purchase->user->getId()) ?: new User();
            return $purchase;
        }

        return null;
    }
}
