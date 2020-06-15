<?php
namespace App\Payment\General;

use App\Managers\UserManager;
use App\Models\Purchase;
use App\Models\User;
use App\PromoCode\PromoCodeService;

class PurchaseSerializer
{
    /** @var PromoCodeService */
    private $promoCodeService;

    /** @var UserManager */
    private $userManager;

    public function __construct(PromoCodeService $promoCodeService, UserManager $userManager)
    {
        $this->promoCodeService = $promoCodeService;
        $this->userManager = $userManager;
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
        if (!$purchase instanceof Purchase) {
            return null;
        }

        // Fix: Refresh user to avoid bugs linked with user wallet
        $purchase->user = $this->userManager->get($purchase->user->getId()) ?: new User();

        // Refresh promo code in case somebody else used it in a meantime
        $promoCode = $purchase->getPromoCode();
        if ($promoCode) {
            $freshPromoCode = $this->promoCodeService->findApplicablePromoCode(
                $promoCode->getCode(),
                $purchase
            );
            $purchase->setPromoCode($freshPromoCode);
        }

        return $purchase;
    }
}
