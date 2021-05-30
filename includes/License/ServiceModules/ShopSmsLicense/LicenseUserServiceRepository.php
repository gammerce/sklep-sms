<?php
namespace App\License\ServiceModules\ShopSmsLicense;

use App\License\Models\LicenseUserService;

class LicenseUserServiceRepository
{
    public function mapToModel(array $data): LicenseUserService
    {
        return new LicenseUserService(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["user_id"]),
            as_int($data["expire"]),
            as_string($data["comment"]),
            as_string($data["identifier"]),
            as_string($data["email"]),
            as_int($data["cost_daily"]),
            (bool) $data["platform_amxmodx"],
            (bool) $data["platform_sourcemod"]
        );
    }
}
