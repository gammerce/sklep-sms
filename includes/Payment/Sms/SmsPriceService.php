<?php
namespace App\Payment\Sms;

use App\Models\Purchase;
use App\Models\SmsNumber;
use App\PromoCode\PromoCodeService;
use App\Support\PriceTextService;
use App\Support\Money;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;

class SmsPriceService
{
    /** @var Settings */
    private $settings;

    /** @var PromoCodeService */
    private $promoCodeService;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(
        Settings $settings,
        PromoCodeService $promoCodeService,
        PriceTextService $priceTextService
    ) {
        $this->settings = $settings;
        $this->promoCodeService = $promoCodeService;
        $this->priceTextService = $priceTextService;
    }

    /**
     * @param Money $smsPrice
     * @param SupportSms $paymentModule
     * @return bool
     */
    public function isPriceAvailable(Money $smsPrice, SupportSms $paymentModule)
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

    /**
     * @param Money $smsPrice
     * @param SupportSms $paymentModule
     * @return SmsNumber|null
     */
    public function getNumber(Money $smsPrice, SupportSms $paymentModule)
    {
        $smsNumbers = $paymentModule->getSmsNumbers();

        foreach ($smsNumbers as $smsNumber) {
            if ($smsNumber->getPrice()->equal($smsPrice)) {
                return $smsNumber;
            }
        }

        return null;
    }

    /**
     * @param Money $smsPrice
     * @param SupportSms $paymentModule
     * @return Money
     */
    public function getProvision(Money $smsPrice, SupportSms $paymentModule)
    {
        $smsNumbers = $paymentModule->getSmsNumbers();

        foreach ($smsNumbers as $smsNumber) {
            if ($smsNumber->getPrice()->equal($smsPrice)) {
                return $smsNumber->getProvision();
            }
        }

        return get_sms_provision($smsPrice);
    }

    /**
     * @param Money $smsPrice
     * @return Money
     */
    public function getGross(Money $smsPrice)
    {
        return new Money(ceil($smsPrice->asInt() * $this->settings->getVat()));
    }

    /**
     * @param Purchase $purchase
     * @return Money|null
     */
    public function getPrice(Purchase $purchase)
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

    /**
     * @param Purchase $purchase
     * @return array
     */
    public function getOldAndNewPrice(Purchase $purchase)
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
