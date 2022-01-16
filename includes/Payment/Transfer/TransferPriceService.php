<?php
namespace App\Payment\Transfer;

use App\Managers\ServiceManager;
use App\Models\Purchase;
use App\PromoCode\PromoCodeService;
use App\Support\Money;
use App\Support\PriceTextService;

class TransferPriceService
{
    private PromoCodeService $promoCodeService;
    private PriceTextService $priceTextService;
    private ServiceManager $serviceManager;

    public function __construct(
        PromoCodeService $promoCodeService,
        PriceTextService $priceTextService,
        ServiceManager $serviceManager
    ) {
        $this->promoCodeService = $promoCodeService;
        $this->priceTextService = $priceTextService;
        $this->serviceManager = $serviceManager;
    }

    public function getPrice(Purchase $purchase): ?Money
    {
        $price = $this->getPriceMoney($purchase);
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
        $price = $this->getPriceMoney($purchase);
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

    private function getPriceMoney(Purchase $purchase): ?Money
    {
        $service = $this->serviceManager->get($purchase->getServiceId());
        $price = as_money($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER));

        if ($service && $price) {
            return $price->multiply(1.0 + $service->getTaxRate() / 100.0);
        }

        return $price;
    }
}
