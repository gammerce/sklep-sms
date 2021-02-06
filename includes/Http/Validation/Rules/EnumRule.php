<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class EnumRule extends BaseRule
{
    private string $enumClass;

    public function __construct($enumClass)
    {
        parent::__construct();
        $this->enumClass = $enumClass;
    }

    public function validate($attribute, $value, array $data): void
    {
        $enumClass = $this->enumClass;

        if (!$enumClass::isValid($value)) {
            throw new ValidationException($this->lang->t("invalid_value"));
        }
    }
}
