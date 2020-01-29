<?php
namespace App\ServiceModules\ExtraFlags\Rules;

use App\Http\Validation\BaseRule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;

class ExtraFlagTypeListRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (!is_array($value)) {
            return ["Invalid type"];
        }

        $allowedTypes = ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP | ExtraFlagType::TYPE_SID;

        foreach ($value as $type) {
            if (!($type & $allowedTypes)) {
                return [$this->lang->t('wrong_type_chosen')];
            }
        }

        return [];
    }
}
