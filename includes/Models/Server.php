<?php
namespace App\Models;

use App\Server\Platform;

class Server
{
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

    /** @var int[] */
    private $transferPlatformIds;

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
        array $transferPlatformIds,
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
        $this->transferPlatformIds = $transferPlatformIds;
        $this->type = $type;
        $this->version = $version;
        $this->lastActiveAt = $lastActiveAt;
        $this->token = $token;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return Platform|null
     */
    public function getType()
    {
        return as_server_type($this->type);
    }

    /**
     * @return int|null
     */
    public function getSmsPlatformId()
    {
        return $this->smsPlatformId;
    }

    /**
     * @return int[]
     */
    public function getTransferPlatformIds()
    {
        return $this->transferPlatformIds;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getLastActiveAt()
    {
        return $this->lastActiveAt;
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }
}
