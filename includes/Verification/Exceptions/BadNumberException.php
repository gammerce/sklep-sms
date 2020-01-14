<?php
namespace App\Verification\Exceptions;

// TODO Replace tariff id

/**
 * Given sms code was valid but not for a given tariff
 * e.g. somebody sent sms to cheaper a number
 */
class BadNumberException extends SmsPaymentException
{
    protected $errorCode = "bad_number";

    /** @var int|null */
    public $smsPrice;

    public function __construct($smsPrice)
    {
        parent::__construct();
        $this->smsPrice = $smsPrice;
    }
}
