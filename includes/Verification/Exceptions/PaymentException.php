<?php
namespace App\Verification\Exceptions;

use Exception;

abstract class PaymentException extends Exception
{
    protected $errorCode;

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
