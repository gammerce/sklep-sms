<?php
namespace App\Verification\Exceptions;

class BadDataException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "bad_data");
    }
}