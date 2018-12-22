<?php
namespace App\Verification\Exceptions;

class WrongCredentialsException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "wrong_credentials");
    }
}