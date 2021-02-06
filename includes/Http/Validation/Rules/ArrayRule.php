<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class ArrayRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (!is_array($value)) {
            throw new ValidationException($this->lang->t("field_array"));
        }
    }

    public function breaksPipelineOnWarning(): bool
    {
        return true;
    }
}
