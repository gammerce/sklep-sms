<?php
namespace App\Exceptions;

use Exception;

class AccessProhibitedException extends Exception
{
    public function __construct(
        $message = "Access prohibited",
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
