<?php
namespace App\Payment\General;

use App\Managers\UserManager;
use App\Models\Purchase;
use App\PromoCode\PromoCodeService;
use ReflectionClass;

class PurchaseSerializer
{
    private PromoCodeService $promoCodeService;
    private UserManager $userManager;

    public function __construct(PromoCodeService $promoCodeService, UserManager $userManager)
    {
        $this->promoCodeService = $promoCodeService;
        $this->userManager = $userManager;
    }

    public function serialize(Purchase $purchase): string
    {
        $clonedPurchase = clone $purchase;
        $reflectionClass = new ReflectionClass(Purchase::class);

        $userProperty = $reflectionClass->getProperty("user");
        $userProperty->setValue($clonedPurchase, $purchase->user->getId());

        if ($purchase->getPromoCode()) {
            $promoCodeProperty = $reflectionClass->getProperty("promoCode");
            $promoCodeProperty->setAccessible(true);
            $promoCodeProperty->setValue($clonedPurchase, $purchase->getPromoCode()->getCode());
        }

        return serialize($clonedPurchase);
    }

    /**
     * @param string $content
     * @return Purchase|null
     */
    public function deserialize($content): ?Purchase
    {
        /** @var Purchase $purchase */
        $purchase = unserialize($content);

        if (!($purchase instanceof Purchase)) {
            return null;
        }

        $reflectionClass = new ReflectionClass(Purchase::class);
        $userProperty = $reflectionClass->getProperty("user");
        $userId = $userProperty->getValue($purchase);

        $promoCodeProperty = $reflectionClass->getProperty("promoCode");
        $promoCodeProperty->setAccessible(true);
        $code = $promoCodeProperty->getValue($purchase);

        $user = $this->userManager->get($userId);
        $purchase->user = $user;

        if ($code) {
            $promoCode = $this->promoCodeService->findApplicablePromoCode($code, $purchase);
            $purchase->setPromoCode($promoCode);
        }

        return $purchase;
    }
}
