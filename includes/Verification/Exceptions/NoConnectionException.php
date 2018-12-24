<?php
namespace App\Verification\Exceptions;

/**
 * Could not connect to the api server
 */
class NoConnectionException extends SmsPaymentException
{
    protected $errorCode = "no_connection";
}
