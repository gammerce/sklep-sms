<?php
namespace App\ServiceModules\ExtraFlags\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;

class ExtraFlagTypeRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        $allowedTypes = ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP | ExtraFlagType::TYPE_SID;

        if (!($value & $allowedTypes)) {
            throw new ValidationException($this->lang->t("wrong_type_chosen"));
        }
    }
}
