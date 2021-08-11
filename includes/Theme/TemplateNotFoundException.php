<?php
namespace App\Theme;

use Exception;

class TemplateNotFoundException extends Exception
{
    public function __construct($message = "Template was not found", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
