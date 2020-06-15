<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class ArrayRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (!is_array($value)) {
            return [$this->lang->t("field_array")];
        }

        return [];
    }
}
