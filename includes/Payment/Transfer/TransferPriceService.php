<?php
namespace App\Payment\Transfer;

use App\Models\Purchase;
use App\PromoCode\PromoCodeService;
use App\Support\PriceTextService;
use App\Support\Money;

class TransferPriceService
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
        $price = as_money($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER));
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
        $price = as_money($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER));
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
