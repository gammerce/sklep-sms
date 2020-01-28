<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class RequiredRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        return has_value($value) ? [] : [$this->lang->t('field_no_empty')];
    }
}
