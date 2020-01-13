<?php
namespace App\Models;

class SmsNumber
{
    /** @var string */
    private $smsNumber;

    /** @var string */
    private $paymentModuleId;

    public function __construct($smsNumber, $paymentModuleId)
    {
        $this->smsNumber = $smsNumber;
        $this->paymentModuleId = $paymentModuleId;
    }

    /**
     * @return string
     */
    public function getSmsNumber()
    {
        return $this->smsNumber;
    }

    /**
     * @return string
     */
    public function getPaymentModuleId()
    {
        return $this->paymentModuleId;
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return get_sms_cost($this->smsNumber);
    }
}
