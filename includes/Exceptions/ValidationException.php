<?php
namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    /** @var array */
    public $warnings;

    /** @var array */
    public $data;

    public function __construct(array $warnings, array $data = [])
    {
        parent::__construct("Validation exception");
        $this->warnings = $warnings;
        $this->data = $data;
    }
}
