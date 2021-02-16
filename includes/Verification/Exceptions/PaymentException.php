<?php
namespace App\Verification\Exceptions;

use Exception;

abstract class PaymentException extends Exception
{
    protected string $errorCode;

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
