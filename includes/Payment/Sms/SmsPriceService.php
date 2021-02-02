<?php
namespace App\Payment\Sms;

use App\Models\Purchase;
use App\Models\SmsNumber;
use App\PromoCode\PromoCodeService;
use App\Support\Money;
use App\Support\PriceTextService;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;

class SmsPriceService
{
    private Settings $settings;
    private PromoCodeService $promoCodeService;
    private PriceTextService $priceTextService;

    public function __construct(
        Settings $settings,
        PromoCodeService $promoCodeService,
        PriceTextService $priceTextService
    ) {
        $this->settings = $settings;
        $this->promoCodeService = $promoCodeService;
        $this->priceTextService = $priceTextService;
    }

    public function isPriceAvailable(Money $smsPrice, SupportSms $paymentModule): bool
    {
        if ($smsPrice->equal(0)) {
            return true;
        }

        $smsNumbers = $paymentModule->getSmsNumbers();

        foreach ($smsNumbers as $smsNumber) {
            if ($smsNumber->getPrice()->equal($smsPrice)) {
                return true;
            }
        }

        return false;
    }

    public function getNumber(Money $smsPrice, SupportSms $paymentModule): ?SmsNumber
    {
        $smsNumbers = $paymentModule->getSmsNumbers();

        foreach ($smsNumbers as $smsNumber) {
            if ($smsNumber->getPrice()->equal($smsPrice)) {
                return $smsNumber;
            }
        }

        return null;
    }

    public function getProvision(Money $smsPrice, SupportSms $paymentModule): Money
    {
        $smsNumbers = $paymentModule->getSmsNumbers();

        foreach ($smsNumbers as $smsNumber) {
            if ($smsNumber->getPrice()->equal($smsPrice)) {
                return $smsNumber->getProvision();
            }
        }

        return get_sms_provision($smsPrice);
    }

    public function getGross(Money $smsPrice): Money
    {
        return new Money(ceil($smsPrice->asInt() * $this->settings->getVat()));
    }

    public function getPrice(Purchase $purchase): ?Money
    {
        $price = as_money($purchase->getPayment(Purchase::PAYMENT_PRICE_SMS));

        if ($price === null) {
            return null;
        }

        $promoCode = $purchase->getPromoCode();
        if ($promoCode) {
            $discountedPrice = $this->promoCodeService->applyDiscount($promoCode, $price);

            // We should return value only if a discount covers 100% of a price
            if ($discountedPrice->equal(0)) {
                return new Money(0);
            }

            // Sms payment should not be available if promo code is applied
            return null;
        }

        return $price;
    }

    public function getOldAndNewPrice(Purchase $purchase): array
    {
        $price = as_money($purchase->getPayment(Purchase::PAYMENT_PRICE_SMS));
        $promoCode = $purchase->getPromoCode();

        if ($promoCode) {
            $discountedPrice = $this->promoCodeService->applyDiscount($promoCode, $price);

            return [
                "price" => $this->priceTextService->getPriceGrossText($discountedPrice),
                "old_price" => $this->priceTextService->getPlainPriceGross($price),
            ];
        }

        return [
            "price" => $this->priceTextService->getPriceGrossText($price),
        ];
    }
}
