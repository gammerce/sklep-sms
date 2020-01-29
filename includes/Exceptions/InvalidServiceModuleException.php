<?php
namespace App\Exceptions;

use RuntimeException;
use Throwable;

class InvalidServiceModuleException extends RuntimeException
{
    public function __construct(
        $message = "Invalid service module",
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
