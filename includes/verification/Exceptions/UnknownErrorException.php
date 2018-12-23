<?php
namespace App\Verification\Exceptions;

/**
 * Something happened that should not happen
 */
class UnknownErrorException extends SmsPaymentException
{
    public function __construct($message = "")
    {
        parent::__construct($message, "unknown_error");
    }
}