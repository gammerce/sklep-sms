<?php
namespace App\Verification\Exceptions;

/**
 * Could not connect to the api server
 */
class NoConnectionException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "no_connection");
    }
}