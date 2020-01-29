<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class SteamIdRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        if (!is_steam_id_valid($value) || strlen($value) > 32) {
            return [$this->lang->t('wrong_sid')];
        }

        return [];
    }
}
