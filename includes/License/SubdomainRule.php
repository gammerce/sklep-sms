<?php
namespace App\License;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class SubdomainRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        if (!preg_match("/^[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?$/", $value)) {
            throw new ValidationException($this->lang->t("invalid_subdomain"));
        }
    }
}
