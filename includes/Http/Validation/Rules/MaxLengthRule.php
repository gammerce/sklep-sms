<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class MaxLengthRule extends BaseRule
{
    private int $length;

    public function __construct($value)
    {
        parent::__construct();
        $this->length = $value;
    }

    public function validate($attribute, $value, array $data): void
    {
        if (strlen($value) > $this->length) {
            throw new ValidationException($this->lang->t("field_length_max_warn", $this->length));
        }
    }
}
