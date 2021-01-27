<?php
namespace App\ServiceModules\ExtraFlags\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;

class ExtraFlagTypeListRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        assert(is_array($value));

        $allowedTypes = ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP | ExtraFlagType::TYPE_SID;

        foreach ($value as $type) {
            if (!($type & $allowedTypes)) {
                throw new ValidationException($this->lang->t("wrong_type_chosen"));
            }
        }
    }
}
