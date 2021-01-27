<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class IntegerCommaSeparatedListRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        $groups = explode(",", $value);

        foreach ($groups as $group) {
            if (!my_is_integer($group)) {
                throw new ValidationException($this->lang->t("group_not_integer"));
            }
        }
    }
}
