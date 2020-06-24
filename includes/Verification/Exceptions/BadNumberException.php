<?php
namespace App\Verification\Exceptions;

/**
 * Given sms code was valid but not for a sms price
 * e.g. somebody sent sms to cheaper a number
 */
class BadNumberException extends SmsPaymentException
{
    protected $errorCode = "bad_number";

    /**
     * Sms net price in grosze
     *
     * @var int|null
     */
    public $smsPrice;

    public function __construct($smsPrice)
    {
        parent::__construct();
        $this->smsPrice = $smsPrice;
    }
}
