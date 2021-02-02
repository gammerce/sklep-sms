<?php
namespace App\License\Models;

use App\Models\UserService;

class LicenseUserService extends UserService
{
    private string $identifier;
    private string $email;
    private int $costDaily;
    private bool $platformAmxModX;
    private bool $platformSourceMod;
    private int $externalLicenseId;

    public function __construct(
        $id,
        $serviceId,
        $uid,
        $expire,
        $identifier,
        $externalLicenseId,
        $email,
        $costDaily,
        $platformAmxModX,
        $platformSourceMod
    ) {
        parent::__construct($id, $serviceId, $uid, $expire);

        $this->identifier = $identifier;
        $this->email = $email;
        $this->costDaily = $costDaily;
        $this->platformAmxModX = $platformAmxModX;
        $this->platformSourceMod = $platformSourceMod;
        $this->externalLicenseId = $externalLicenseId;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCostDaily(): int
    {
        return $this->costDaily;
    }

    public function hasPlatformAmxModX(): bool
    {
        return $this->platformAmxModX;
    }

    public function hasPlatformSourceMod(): bool
    {
        return $this->platformSourceMod;
    }

    public function getExternalLicenseId(): int
    {
        return $this->externalLicenseId;
    }
}
