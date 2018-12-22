<?php
namespace App\Verification\Exceptions;

class BadCodeException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "bad_code");
    }
}