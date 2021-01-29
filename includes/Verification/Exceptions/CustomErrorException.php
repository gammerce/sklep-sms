<?php
namespace App\Verification\Exceptions;

class CustomErrorException extends SmsPaymentException
{
    protected string $errorCode = "custom_error";
}
