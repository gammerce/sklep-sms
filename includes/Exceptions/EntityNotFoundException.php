<?php
namespace App\Exceptions;

use Exception;

class EntityNotFoundException extends Exception
{
    public function __construct($message = "Model not found", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
