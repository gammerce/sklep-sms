<?php
namespace App\Services;

use App\Models\SmsNumber;
use App\System\Settings;
use App\Verification\Abstracts\PaymentModule;

// TODO Use SupportSms instead of PaymentModule

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
     * @param PaymentModule $paymentModule
     * @return bool
     */
    public function isPriceAvailable($smsPrice, PaymentModule $paymentModule)
    {
        // TODO Implement
        return true;
    }

    /**
     * @param int $smsPrice
     * @param PaymentModule $paymentModule
     * @return SmsNumber
     */
    public function getNumber($smsPrice, PaymentModule $paymentModule)
    {
        // TODO Implement
        return null;
    }

    /**
     * @param int $smsPrice
     * @param PaymentModule|null $paymentModule
     * @return int
     */
    public function getProvision($smsPrice, PaymentModule $paymentModule = null)
    {
        // TODO Implement
        return (int) ($smsPrice / 2);
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
