<?php
namespace App\Verification\Exceptions;

class CustomErrorException extends SmsPaymentException
{
    protected $errorCode = "custom_error";
}
