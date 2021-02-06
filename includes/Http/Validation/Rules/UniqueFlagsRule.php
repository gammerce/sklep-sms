<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class UniqueFlagsRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (implode("", array_unique(str_split($value))) !== $value) {
            throw new ValidationException($this->lang->t("same_flags"));
        }
    }
}
