<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class PostalCodeRule extends RegexRule
{
    protected string $errorMessage = "field_postal_code_warn";

    public function __construct(string $pattern = "/^\d{2}-\d{3}$/")
    {
        parent::__construct($pattern);
    }
}
