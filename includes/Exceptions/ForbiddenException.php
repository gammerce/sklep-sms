<?php
namespace App\Exceptions;

use Exception;

class ForbiddenException extends Exception
{
    public function __construct($message = "Forbidden", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
