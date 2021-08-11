<?php
namespace App\License;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class SubdomainRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (!preg_match("/^[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?$/", $value)) {
            throw new ValidationException($this->lang->t("invalid_subdomain"));
        }
    }
}
