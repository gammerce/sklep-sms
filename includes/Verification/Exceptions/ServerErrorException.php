<?php
namespace App\Verification\Exceptions;

class ServerErrorException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "server_error");
    }
}