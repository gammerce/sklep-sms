<?php
namespace App\Models;

use App\Support\Money;

class SmsNumber
{
    private string $smsNumber;
    private Money $provision;

    /**
     * @param string $smsNumber
     * @param Money|int|null $provision
     */
    public function __construct($smsNumber, $provision = null)
    {
        $this->smsNumber = $smsNumber;
        $this->provision = new Money($provision ?: get_sms_provision(get_sms_cost($smsNumber)));
    }

    public function getNumber(): string
    {
        return $this->smsNumber;
    }

    public function getProvision(): Money
    {
        return $this->provision;
    }

    public function getPrice(): Money
    {
        return get_sms_cost($this->smsNumber);
    }
}
