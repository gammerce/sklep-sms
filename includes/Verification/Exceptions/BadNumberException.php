<?php
namespace App\Verification\Exceptions;

class BadNumberException extends SmsPaymentException
{
    /** @var int */
    public $tariffId;

    public function __construct($tariffId)
    {
        $this->tariffId = $tariffId;
    }
}