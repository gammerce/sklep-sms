<?php
namespace App\Verification\Exceptions;

/**
 * Not all required data was given (api, token etc.)
 */
class InsufficientDataException extends SmsPaymentException
{
    protected $errorCode = "insufficient_data";
}
