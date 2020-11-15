<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Http\Validation\EmptyRule;

class RequiredRule extends BaseRule implements EmptyRule
{
    public function validate($attribute, $value, array $data)
    {
        return has_value($value) ? [] : [$this->lang->t("field_no_empty")];
    }
}
