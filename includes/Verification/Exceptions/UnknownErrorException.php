<?php
namespace App\Verification\Exceptions;

class UnknownErrorException extends SmsPaymentException
{
    public function __construct($message = "")
    {
        parent::__construct($message, "unknown");
    }
}