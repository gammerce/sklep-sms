<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Support\SteamIDConverter;

class SteamIdRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        $converter = new SteamIDConverter();

        if (!$converter->isSteamID($value)) {
            throw new ValidationException($this->lang->t("wrong_sid"));
        }
    }
}
