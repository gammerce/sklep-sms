<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class YesNoRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (!in_array($value, ["1", "0"])) {
            return [$this->lang->t("only_yes_no")];
        }

        return [];
    }
}
