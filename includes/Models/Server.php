<?php
namespace App\Models;

class Server
{
    const TYPE_AMXMODX = 'amxx';
    const TYPE_SOURCEMOD = 'sm';

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
    private $smsService;

    public function __construct($id, $name, $ip, $port, $smsService = '', $type = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->ip = $ip;
        $this->port = $port;
        $this->smsService = $smsService;
        $this->type = $type;
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

    public function getSmsService()
    {
        return $this->smsService;
    }
}
