<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class IntegerRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (!my_is_integer($value)) {
            throw new ValidationException($this->lang->t("field_integer"));
        }
    }
}
