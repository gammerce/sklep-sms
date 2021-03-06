<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class UsernameRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        $warnings = [];

        if (strlen($value) < 2) {
            $warnings[] = $this->lang->t("field_length_min_warn", 2);
        }

        if ($value !== htmlspecialchars($value)) {
            $warnings[] = $this->lang->t("username_chars_warn");
        }

        if (!empty($warnings)) {
            throw new ValidationException($warnings);
        }
    }
}
