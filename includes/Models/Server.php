<?php
namespace App\Models;

class Server
{
    const TYPE_AMXMODX = 'amxmodx';
    const TYPE_SOURCEMOD = 'sourcemod';

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

    /** @var string */
    private $smsPlatform;

    /** @var string */
    private $version;

    /** @var string|null */
    private $lastActiveAt;

    public function __construct(
        $id,
        $name,
        $ip,
        $port,
        $smsPlatform,
        $type,
        $version,
        $lastActiveAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->ip = $ip;
        $this->port = $port;
        $this->smsPlatform = $smsPlatform;
        $this->type = $type;
        $this->version = $version;
        $this->lastActiveAt = $lastActiveAt;
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
        return $this->smsPlatform;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getLastActiveAt()
    {
        return $this->lastActiveAt;
    }
}
