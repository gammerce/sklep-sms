<?php
namespace App\License\ServiceModules\ShopSmsLicense\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class LicenseEnginesRule extends BaseRule
{
    public function validate($attribute, $value, array $data): void
    {
        $amxModX = array_get($data, "platform_amxmodx");
        $sourceMod = array_get($data, "platform_sourcemod");

        if ($amxModX == "0" && $sourceMod == "0") {
            throw new ValidationException($this->lang->t("no_engine_choosen"));
        }
    }

    public function acceptsEmptyValue(): bool
    {
        return true;
    }
}
