<?php
namespace App\Verification\Exceptions;

class BadNumberException extends SmsPaymentException
{
    /** @var int|null */
    public $tariffId;

    public function __construct($tariffId)
    {
        $this->tariffId = $tariffId;
    }
}