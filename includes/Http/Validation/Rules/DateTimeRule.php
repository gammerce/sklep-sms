<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class DateTimeRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (strtotime($value) === false) {
            return [$this->lang->t("wrong_date_format")];
        }

        return [];
    }
}
