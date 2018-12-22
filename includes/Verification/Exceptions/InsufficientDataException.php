<?php
namespace App\Verification\Exceptions;

/**
 * Not all required data was given (api, token etc.)
 */
class InsufficientDataException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "insufficient_data");
    }
}