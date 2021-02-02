<?php
namespace App\Verification\Exceptions;

/**
 * Unknown error occurred on the API side
 */
class ExternalErrorException extends SmsPaymentException
{
    protected string $errorCode = "external_error";
}
