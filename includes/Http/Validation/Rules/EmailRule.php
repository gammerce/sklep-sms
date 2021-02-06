<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class EmailRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException($this->lang->t("wrong_email"));
        }
    }
}
