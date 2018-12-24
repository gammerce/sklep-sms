<?php
namespace App\Verification\Exceptions;

/**
 * Given credentials (api, token, password, key etc.) were incorrect
 */
class WrongCredentialsException extends SmsPaymentException
{
    protected $errorCode = "wrong_credentials";
}
