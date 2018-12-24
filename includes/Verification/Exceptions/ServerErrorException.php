<?php
namespace App\Verification\Exceptions;

/**
 * Something bad happened on the api side
 *    unhandled exceptions
 *    5xx status code
 *    invalid response format
 */
class ServerErrorException extends SmsPaymentException
{
    protected $errorCode = "server_error";
}
