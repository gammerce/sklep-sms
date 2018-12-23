<?php
namespace App\Verification\Exceptions;

/**
 * Given sms code was invalid
 */
class BadCodeException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "bad_code");
    }
}