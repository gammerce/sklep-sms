<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class ConfirmedRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if ($value !== array_get($data, "{$attribute}_repeat")) {
            throw new ValidationException($this->lang->t("different_values"));
        }
    }
}
