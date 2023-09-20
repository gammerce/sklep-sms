<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class RegexRule extends BaseRule
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        parent::__construct();
        $this->pattern = $pattern;
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!preg_match($this->pattern, $value)) {
            throw new ValidationException($this->lang->t("field_regex_warn"));
        }
    }
}
