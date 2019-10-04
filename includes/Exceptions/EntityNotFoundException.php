<?php
namespace App\Exceptions;

use Exception;
use Throwable;

class EntityNotFoundException extends Exception
{
    public function __construct($message = "Model not found", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
