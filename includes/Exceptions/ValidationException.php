<?php
namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Arrayable;

class ValidationException extends Exception
{
    /** @var array */
    public $warnings;

    /** @var array */
    public $data;

    /**
     * @param array|Arrayable $warnings
     * @param array $data
     */
    public function __construct($warnings, array $data = [])
    {
        parent::__construct("Validation exception");
        $this->warnings = $warnings instanceof Arrayable ? $warnings->toArray() : $warnings;
        $this->data = $data;
    }
}
