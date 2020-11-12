<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class PasswordRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (strlen($value) < 6) {
            return [$this->lang->t("field_length_min_warn", 6)];
        }

        return [];
    }
}
