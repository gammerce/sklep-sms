<?php
namespace App\Exceptions;

use RuntimeException;
use Throwable;

class InvalidPaymentModuleException extends RuntimeException
{
    public function __construct(
        $message = "Invalid payment module",
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
