<?php
namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    /** @var array */
    public $warnings;

    public function __construct(array $warnings)
    {
        parent::__construct("Validation exception");
        $this->warnings = $warnings;
    }
}
