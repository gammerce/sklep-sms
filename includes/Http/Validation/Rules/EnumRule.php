<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class EnumRule extends BaseRule
{
    private $enumClass;

    public function __construct($enumClass)
    {
        parent::__construct();
        $this->enumClass = $enumClass;
    }

    public function validate($attribute, $value, array $data)
    {
        $enumClass = $this->enumClass;

        if (!$enumClass::isValid($value)) {
            return [$this->lang->t("invalid_value")];
        }

        return [];
    }
}
