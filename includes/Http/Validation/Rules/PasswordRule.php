<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class PasswordRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (strlen($value) < 6) {
            throw new ValidationException($this->lang->t("field_length_min_warn", 6));
        }
    }
}
