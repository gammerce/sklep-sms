<?php
namespace App\Payment\Sms;

use App\Models\Purchase;
use App\Models\SmsNumber;
use App\PromoCode\PromoCodeService;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;

class SmsPriceService
{
    /** @var Settings */
    private $settings;

    /** @var PromoCodeService */
    private $promoCodeService;

    public function __construct(Settings $settings, PromoCodeService $promoCodeService)
    {
        $this->settings = $settings;
        $this->promoCodeService = $promoCodeService;
    }

    /**
     * @param int $smsPrice
     * @param SupportSms $paymentModule
     * @return bool
     */
    public function isPriceAvailable($smsPrice, SupportSms $paymentModule)
    {
        $smsNumbers = $paymentModule::getSmsNumbers();

        foreach ($smsNumbers as $smsNumber) {
            if ($smsNumber->getPrice() === $smsPrice) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $smsPrice
     * @param SupportSms $paymentModule
     * @return SmsNumber|null
     */
    public function getNumber($smsPrice, SupportSms $paymentModule)
    {
        $smsNumbers = $paymentModule::getSmsNumbers();

        foreach ($smsNumbers as $smsNumber) {
            if ($smsNumber->getPrice() === $smsPrice) {
                return $smsNumber;
            }
        }

        return null;
    }

    /**
     * @param int $smsPrice
     * @param SupportSms $paymentModule
     * @return int
     */
    public function getProvision($smsPrice, SupportSms $paymentModule)
    {
        $smsNumbers = $paymentModule::getSmsNumbers();

        foreach ($smsNumbers as $smsNumber) {
            if ($smsNumber->getPrice() === $smsPrice) {
                return $smsNumber->getProvision();
            }
        }

        return get_sms_provision($smsPrice);
    }

    /**
     * @param int $smsPrice
     * @return int
     */
    public function getGross($smsPrice)
    {
        return (int) ceil($smsPrice * $this->settings->getVat());
    }

    /**
     * @param Purchase $purchase
     * @return int|null
     */
    public function getPrice(Purchase $purchase)
    {
        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS);
        if ($price === null) {
            return null;
        }

        $promoCode = $purchase->getPromoCode();
        if ($promoCode) {
            $discountedPrice = $this->promoCodeService->applyDiscount($promoCode, $price);

            if ($discountedPrice === 0) {
                return 0;
            }

            // Sms payment should not be available if promo code is applied
            return null;
        }

        return $price;
    }
}
