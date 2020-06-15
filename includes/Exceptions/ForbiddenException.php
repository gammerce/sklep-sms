<?php
namespace App\Exceptions;

use Exception;

class ForbiddenException extends Exception
{
    public function __construct($message = "Forbidden", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
