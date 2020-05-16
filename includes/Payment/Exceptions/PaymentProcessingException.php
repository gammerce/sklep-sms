<?php
namespace App\Payment\Exceptions;

use Exception;

class PaymentProcessingException extends Exception
{
    public function __construct($code, $message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->code = $code;
    }
}
