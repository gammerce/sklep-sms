<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class RegexRule extends BaseRule
{
    protected string $pattern;
    protected string $errorMessage = "field_regex_warn";

    public function __construct(string $pattern)
    {
        parent::__construct();
        $this->pattern = $pattern;
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!preg_match($this->pattern, $value)) {
            throw new ValidationException($this->lang->t($this->errorMessage));
        }
    }
}
