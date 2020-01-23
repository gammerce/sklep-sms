<?php
namespace App\Models;

class LicenseUserService extends UserService
{
    /** @var string */
    private $identifier;

    /** @var string */
    private $email;

    /** @var int */
    private $costDaily;

    /** @var bool */
    private $platformAmxModX;

    /** @var bool */
    private $platformSourceMod;

    /** @var int */
    private $externalLicenseId;

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
