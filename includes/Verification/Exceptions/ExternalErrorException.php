<?php
namespace App\Verification\Exceptions;

/**
 * Unknown error occurred on the API side
 */
class ExternalErrorException extends SmsPaymentException
{
    public function __construct($message = "")
    {
        parent::__construct($message, "external_error");
    }
}