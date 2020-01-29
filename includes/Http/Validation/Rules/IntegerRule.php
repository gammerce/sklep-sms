<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class IntegerRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (!my_is_integer($value)) {
            return [$this->lang->t('field_integer')];
        }

        return [];
    }
}
