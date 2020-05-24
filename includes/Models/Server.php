<?php
namespace App\Models;

class Server
{
    const TYPE_AMXMODX = "amxmodx";
    const TYPE_SOURCEMOD = "sourcemod";

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $ip;

    /** @var string */
    private $port;

    /** @var string */
    private $type;

    /** @var int|null */
    private $smsPlatformId;

    /** @var int|null */
    private $transferPlatformId;

    /** @var string */
    private $version;

    /** @var string|null */
    private $lastActiveAt;

    /** @var string|null */
    private $token;

    public function __construct(
        $id,
        $name,
        $ip,
        $port,
        $smsPlatformId,
        $transferPlatformId,
        $type,
        $version,
        $lastActiveAt,
        $token
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->ip = $ip;
        $this->port = $port;
        $this->smsPlatformId = $smsPlatformId;
        $this->transferPlatformId = $transferPlatformId;
        $this->type = $type;
        $this->version = $version;
        $this->lastActiveAt = $lastActiveAt;
        $this->token = $token;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSmsPlatformId()
    {
        return $this->smsPlatformId;
    }

    public function getTransferPlatformId()
    {
        return $this->transferPlatformId;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getLastActiveAt()
    {
        return $this->lastActiveAt;
    }

    public function getToken()
    {
        return $this->token;
    }
}
