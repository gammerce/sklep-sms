<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class FullNameRule extends RegexRule
{
    public function __construct(string $pattern = "/^\p{L}{3,}\s\p{L}{2,}$/u")
    {
        parent::__construct($pattern);
    }
}
