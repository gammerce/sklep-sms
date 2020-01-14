<?php
namespace App\Models;

class SmsNumber
{
    /** @var string */
    private $smsNumber;

    /** @var int */
    private $provision;

    public function __construct($smsNumber, $provision = null)
    {
        $this->smsNumber = $smsNumber;
        $this->provision = $provision ?: get_sms_provision($smsNumber);
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->smsNumber;
    }

    /**
     * @return int
     */
    public function getProvision()
    {
        return $this->provision;
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return get_sms_cost($this->smsNumber);
    }
}
