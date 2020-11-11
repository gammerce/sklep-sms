<?php
namespace App\PromoCode;

use App\Models\PromoCode;
use App\Models\Purchase;
use App\Repositories\PromoCodeRepository;
use App\Support\Money;
use DateTime;

class PromoCodeService
{
    /** @var PromoCodeRepository */
    private $promoCodeRepository;

    public function __construct(PromoCodeRepository $promoCodeRepository)
    {
        $this->promoCodeRepository = $promoCodeRepository;
    }

    /**
     * @param string $promoCode
     * @param Purchase $purchase
     * @return PromoCode|null
     */
    public function findApplicablePromoCode($promoCode, Purchase $purchase)
    {
        if (!strlen($promoCode)) {
            return null;
        }

        $promoCodeModel = $this->promoCodeRepository->findByCode($promoCode);

        if (!$promoCodeModel) {
            return null;
        }

        if ($promoCodeModel->getExpiresAt() && $promoCodeModel->getExpiresAt() < new DateTime()) {
            return null;
        }

        if (
            $promoCodeModel->getRemainingUsage() !== null &&
            $promoCodeModel->getRemainingUsage() <= 0
        ) {
            return null;
        }

        if (
            $promoCodeModel->getUserId() &&
            $promoCodeModel->getUserId() !== $purchase->user->getId()
        ) {
            return null;
        }

        if (
            $promoCodeModel->getServerId() &&
            $promoCodeModel->getServerId() !== $purchase->getOrder(Purchase::ORDER_SERVER)
        ) {
            return null;
        }

        if (
            $promoCodeModel->getServiceId() &&
            $promoCodeModel->getServiceId() !== $purchase->getServiceId()
        ) {
            return null;
        }

        return $promoCodeModel;
    }

    /**
     * @param PromoCode $promoCode
     * @param Money|int $price
     * @return Money
     */
    public function applyDiscount(PromoCode $promoCode, $price)
    {
        switch ($promoCode->getQuantityType()) {
            case QuantityType::FIXED():
                return new Money(max(0, $price - $promoCode->getQuantity()));

            case QuantityType::PERCENTAGE():
                $multiplier = (100 - $promoCode->getQuantity()) / 100;
                return new Money(max(0, ceil($price * $multiplier)));

            default:
                return new Money($price);
        }
    }
}
