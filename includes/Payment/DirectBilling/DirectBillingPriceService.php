<?php
namespace App\Payment\DirectBilling;

use App\Models\Purchase;
use App\PromoCode\PromoCodeService;
use App\Support\Money;
use App\Support\PriceTextService;

class DirectBillingPriceService
{
    /** @var PromoCodeService */
    private $promoCodeService;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(
        PromoCodeService $promoCodeService,
        PriceTextService $priceTextService
    ) {
        $this->promoCodeService = $promoCodeService;
        $this->priceTextService = $priceTextService;
    }

    /**
     * @param Purchase $purchase
     * @return Money|null
     */
    public function getPrice(Purchase $purchase)
    {
        $price = as_money($purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING));
        if ($price === null) {
            return null;
        }

        $promoCode = $purchase->getPromoCode();
        if ($promoCode) {
            return $this->promoCodeService->applyDiscount($promoCode, $price);
        }

        return $price;
    }

    /**
     * @param Purchase $purchase
     * @return array
     */
    public function getOldAndNewPrice(Purchase $purchase)
    {
        $price = as_money($purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING));
        $promoCode = $purchase->getPromoCode();

        if ($promoCode) {
            $discountedPrice = $this->promoCodeService->applyDiscount($promoCode, $price);

            return [
                "price" => $this->priceTextService->getPriceText($discountedPrice),
                "old_price" => $price->asPrice(),
            ];
        }

        return [
            "price" => $this->priceTextService->getPriceText($price),
        ];
    }
}
