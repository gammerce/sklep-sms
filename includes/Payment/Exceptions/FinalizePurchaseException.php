<?php
namespace App\Payment\Exceptions;

use Exception;

class FinalizePurchaseException extends Exception
{
    public function __construct(
        $message = "Finalize purchase exception",
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
