<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class ConfirmedRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if ($value !== array_get($data, "{$attribute}_repeat")) {
            return [$this->lang->t('different_values')];
        }

        return [];
    }
}
