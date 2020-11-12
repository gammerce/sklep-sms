<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class UniqueFlagsRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (implode("", array_unique(str_split($value))) !== $value) {
            return [$this->lang->t("same_flags")];
        }

        return [];
    }
}
