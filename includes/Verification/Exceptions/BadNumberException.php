<?php
namespace App\Verification\Exceptions;

/**
 * Given sms code was valid but not for a given tariff
 * e.g. somebody sent sms to cheaper a number
 */
class BadNumberException extends SmsPaymentException
{
    /** @var int|null */
    public $tariffId;

    public function __construct($tariffId)
    {
        parent::__construct("", "bad_number");
        $this->tariffId = $tariffId;
    }
}