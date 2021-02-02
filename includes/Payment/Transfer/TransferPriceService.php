<?php
namespace App\Payment\Transfer;

use App\Models\Purchase;
use App\PromoCode\PromoCodeService;
use App\Support\Money;
use App\Support\PriceTextService;

class TransferPriceService
{
    private PromoCodeService $promoCodeService;
    private PriceTextService $priceTextService;

    public function __construct(
        PromoCodeService $promoCodeService,
        PriceTextService $priceTextService
    ) {
        $this->promoCodeService = $promoCodeService;
        $this->priceTextService = $priceTextService;
    }

    public function getPrice(Purchase $purchase): ?Money
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

    public function getOldAndNewPrice(Purchase $purchase): array
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
