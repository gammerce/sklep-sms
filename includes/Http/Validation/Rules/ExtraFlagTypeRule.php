<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;

class ExtraFlagTypeRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        $allowedTypes = ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP | ExtraFlagType::TYPE_SID;

        if (!($value & $allowedTypes)) {
            return [$this->lang->t('wrong_type_chosen')];
        }

        return [];
    }
}
