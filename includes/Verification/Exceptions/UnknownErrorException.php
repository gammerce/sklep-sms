<?php
namespace App\Verification\Exceptions;

/**
 * Something happened that should not happen
 */
class UnknownErrorException extends SmsPaymentException
{
    protected $errorCode = "unknown_error";
}
