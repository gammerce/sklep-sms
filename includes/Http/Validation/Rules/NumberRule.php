<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class NumberRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (!is_numeric($value)) {
            return [$this->lang->t("field_must_be_number")];
        }

        return [];
    }
}
