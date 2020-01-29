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
            return check_for_warnings("nick", $value);
        }

        if ($type === ExtraFlagType::TYPE_IP) {
            return check_for_warnings("ip", $value);
        }

        if ($type === ExtraFlagType::TYPE_SID) {
            return check_for_warnings("sid", $value);
        }

        return [];
    }
}
