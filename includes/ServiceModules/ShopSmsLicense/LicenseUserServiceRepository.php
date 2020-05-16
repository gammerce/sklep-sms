<?php
namespace App\ServiceModules\ShopSmsLicense;

use App\Models\LicenseUserService;

class LicenseUserServiceRepository
{
    public function mapToModel(array $data)
    {
        return new LicenseUserService(
            as_int($data["id"]),
            $data["service_id"],
            as_int($data["user_id"]),
            as_int($data["expire"]),
            $data["identifier"],
            $data["external_license_id"],
            $data["email"],
            as_int($data["cost_daily"]),
            (bool) $data["platform_amxmodx"],
            (bool) $data["platform_sourcemod"]
        );
    }
}
