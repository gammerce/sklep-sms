<?php
namespace App\Verification\Exceptions;

use App\Support\Money;

/**
 * Given sms code was valid but not for a sms price
 * e.g. somebody sent sms to cheaper a number
 */
class BadNumberException extends SmsPaymentException
{
    protected string $errorCode = "bad_number";

    /**
     * Sms net price
     */
    private ?Money $smsPrice;

    public function __construct(Money $smsPrice = null)
    {
        parent::__construct();
        $this->smsPrice = $smsPrice;
    }

    /**
     * @return Money
     */
    public function getSmsPrice()
    {
        return $this->smsPrice ?: new Money(0);
    }
}
