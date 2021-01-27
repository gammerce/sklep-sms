<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class DateTimeRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (strtotime($value) === false) {
            throw new ValidationException($this->lang->t("wrong_date_format"));
        }
    }
}
