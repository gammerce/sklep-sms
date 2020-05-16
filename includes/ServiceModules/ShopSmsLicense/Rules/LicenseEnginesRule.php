<?php
namespace App\ServiceModules\ShopSmsLicense\Rules;

use App\Http\Validation\BaseRule;
use App\Http\Validation\EmptyRule;

class LicenseEnginesRule extends BaseRule implements EmptyRule
{
    public function validate($attribute, $value, array $data)
    {
        $amxModX = array_get($data, "platform_amxmodx");
        $sourceMod = array_get($data, "platform_sourcemod");

        if ($amxModX == "0" && $sourceMod == "0") {
            return [$this->lang->t("no_engine_choosen")];
        }

        return [];
    }
}
