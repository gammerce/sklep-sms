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

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return int
     */
    public function getCostDaily()
    {
        return $this->costDaily;
    }

    /**
     * @return bool
     */
    public function hasPlatformAmxModX()
    {
        return $this->platformAmxModX;
    }

    /**
     * @return bool
     */
    public function hasPlatformSourceMod()
    {
        return $this->platformSourceMod;
    }

    /**
     * @return int
     */
    public function getExternalLicenseId()
    {
        return $this->externalLicenseId;
    }
}
