<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class YesNoRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (!in_array($value, ["1", "0"])) {
            throw new ValidationException($this->lang->t("only_yes_no"));
        }
    }
}
