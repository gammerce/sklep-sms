<?php
namespace App\Models;

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
    private $smsService;

    public function __construct($id, $name, $ip, $port, $smsService = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->ip = $ip;
        $this->port = $port;
        $this->smsService = $smsService;
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

    public function getSmsService()
    {
        return $this->smsService;
    }
}