<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class FullNameRule extends RegexRule
{
    public function __construct(string $pattern = "/^\p{L}+\s\p{L}+$/u")
    {
        parent::__construct($pattern);
    }
}
