<?php
namespace App\Payment\Exceptions;

use Exception;

class PaymentProcessingException extends Exception
{
    private $status;

    public function __construct($status, $message)
    {
        parent::__construct($message);
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
