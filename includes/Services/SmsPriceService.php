<?php
namespace App\Services;

use App\Models\SmsNumber;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;

class SmsPriceService
{
    /** @var Settings */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
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
}
