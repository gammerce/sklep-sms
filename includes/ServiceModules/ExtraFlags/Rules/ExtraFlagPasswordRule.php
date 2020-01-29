<?php
namespace App\ServiceModules\ExtraFlags\Rules;

use App\Http\Validation\BaseRule;
use App\Http\Validation\EmptyRule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;

class ExtraFlagPasswordRule extends BaseRule implements EmptyRule
{
    public function validate($attribute, $value, array $data)
    {
        $type = array_get($data, 'type');

        $allowedTypes = ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP;
        if ($type & $allowedTypes && !strlen($value)) {
            return [$this->lang->t('field_no_empty')];
        }

        return [];
    }
}
