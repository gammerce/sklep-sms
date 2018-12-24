<?php
namespace App\Verification\Exceptions;

/**
 * Given sms code was invalid
 */
class BadCodeException extends SmsPaymentException
{
    protected $errorCode = "bad_code";
}
