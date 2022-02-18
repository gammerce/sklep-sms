<?php
namespace App\License;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\License\ServiceModules\ShopSmsLicense\LicenseUserServiceRepository;
use App\Translation\TranslationManager;

class SubdomainRule extends BaseRule
{
    private LicenseUserServiceRepository $licenseUserServiceRepository;

    public function __construct()
    {
        parent::__construct();
        $this->licenseUserServiceRepository = app()->make(LicenseUserServiceRepository::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!preg_match("/^[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?$/", $value)) {
            throw new ValidationException($this->lang->t("invalid_subdomain"));
        }

        if ($this->licenseUserServiceRepository->findBySubdomain($value)) {
            throw new ValidationException($this->lang->t("taken_subdomain"));
        }
    }
}
