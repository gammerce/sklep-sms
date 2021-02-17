<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class RequiredRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (!has_value($value)) {
            throw new ValidationException($this->lang->t("field_no_empty"));
        }
    }

    public function acceptsEmptyValue(): bool
    {
        return true;
    }

    public function breaksPipelineOnWarning(): bool
    {
        return true;
    }
}
