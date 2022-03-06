<?php
namespace App\License;

use App\License\ServiceModules\ShopSmsLicense\LicenseUserServiceRepository;

class LicenseIncomeService
{
    private LicenseUserServiceRepository $licenseUserServiceRepository;

    public function __construct(LicenseUserServiceRepository $licenseUserServiceRepository)
    {
        $this->licenseUserServiceRepository = $licenseUserServiceRepository;
    }

    public function sumDaily(): int
    {
        $sumDaily = 0;
        foreach ($this->licenseUserServiceRepository->all() as $licenseUserService) {
            $sumDaily += $licenseUserService->getCostDaily();
        }
        return $sumDaily;
    }
}
