<?php
namespace App\Verification\Exceptions;

class ExternalApiException extends SmsPaymentException
{
    public function __construct()
    {
        parent::__construct("", "external_api");
    }
}