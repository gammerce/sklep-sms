<?php
namespace App\ServiceModules\ExtraFlags\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;

class ExtraFlagPasswordRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        $type = array_get($data, "type");

        $allowedTypes = ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP;
        if ($type & $allowedTypes && !strlen($value ?? "")) {
            throw new ValidationException($this->lang->t("field_no_empty"));
        }
    }

    public function acceptsEmptyValue(): bool
    {
        return true;
    }
}
