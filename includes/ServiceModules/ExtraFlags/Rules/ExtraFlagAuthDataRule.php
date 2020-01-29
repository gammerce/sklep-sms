<?php
namespace App\ServiceModules\ExtraFlags\Rules;

use App\Http\Validation\BaseRule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;

class ExtraFlagAuthDataRule extends BaseRule
{
    public function validate($attribute, $value, array $data)
    {
        $type = array_get($data, 'type');

        if ($type === ExtraFlagType::TYPE_NICK) {
            if (strlen($value) < 2) {
                return [$this->lang->t('field_length_min_warn', 2)];
            }

            if (strlen($value) > 32) {
                return [$this->lang->t('field_length_max_warn', 32)];
            }

            return [];
        }

        if ($type === ExtraFlagType::TYPE_IP) {
            if (!filter_var($value, FILTER_VALIDATE_IP)) {
                return [$this->lang->t('wrong_ip')];
            }

            return [];
        }

        if ($type === ExtraFlagType::TYPE_SID) {
            if (!is_steam_id_valid($value) || strlen($value) > 32) {
                return [$this->lang->t('wrong_sid')];
            }

            return [];
        }

        return [];
    }
}
