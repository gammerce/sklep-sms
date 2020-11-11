<?php
namespace App\Models;

use App\Support\Money;

class SmsNumber
{
    /** @var string */
    private $smsNumber;

    /** @var Money */
    private $provision;

    /**
     * @param string $smsNumber
     * @param Money|int|null $provision
     */
    public function __construct($smsNumber, $provision = null)
    {
        $this->smsNumber = $smsNumber;
        $this->provision = new Money($provision ?: get_sms_provision(get_sms_cost($smsNumber)));
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->smsNumber;
    }

    /**
     * @return Money
     */
    public function getProvision()
    {
        return $this->provision;
    }

    /**
     * @return Money
     */
    public function getPrice()
    {
        return get_sms_cost($this->smsNumber);
    }
}
