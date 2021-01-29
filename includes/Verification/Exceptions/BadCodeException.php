<?php
namespace App\Verification\Exceptions;

/**
 * Given sms code was invalid
 */
class BadCodeException extends SmsPaymentException
{
    protected string $errorCode = "bad_code";
}
