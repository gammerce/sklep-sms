<?php
namespace App\Payment\Transfer;

use App\Models\Purchase;
use App\PromoCode\PromoCodeService;
use App\Services\PriceTextService;

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
     * @return int|null
     */
    public function getPrice(Purchase $purchase)
    {
        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER);
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
        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER);

        $promoCode = $purchase->getPromoCode();

        if ($promoCode) {
            $discountedPrice = $this->promoCodeService->applyDiscount($promoCode, $price);

            return [
                "price" => $this->priceTextService->getPriceText($discountedPrice),
                "old_price" => $this->priceTextService->getPlainPrice($price),
            ];
        }

        return [
            "price" => $this->priceTextService->getPriceText($price),
        ];
    }
}
