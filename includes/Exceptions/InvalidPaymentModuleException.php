<?php
namespace App\Exceptions;

use RuntimeException;

class InvalidPaymentModuleException extends RuntimeException
{
    public function __construct($message = "Invalid payment module", $code = 0, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
