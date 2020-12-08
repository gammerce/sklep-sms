<?php
namespace App\Exceptions;

use RuntimeException;

class InvalidServiceModuleException extends RuntimeException
{
    public function __construct($message = "Invalid service module", $code = 0, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
