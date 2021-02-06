<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class NumberRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (!is_numeric($value)) {
            throw new ValidationException($this->lang->t("field_must_be_number"));
        }
    }
}
