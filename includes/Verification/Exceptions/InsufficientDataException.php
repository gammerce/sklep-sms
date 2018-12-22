<?php
namespace App\Verification\Exceptions;

class InsufficientDataException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "insufficient_data");
    }
}