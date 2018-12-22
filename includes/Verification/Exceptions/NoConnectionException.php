<?php
namespace App\Verification\Exceptions;

class NoConnectionException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "no_connection");
    }
}