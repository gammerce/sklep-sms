<?php
namespace App\Verification\Exceptions;

/**
 * Given credentials (api, token, password, key etc.) were incorrect
 */
class WrongCredentialsException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "wrong_credentials");
    }
}