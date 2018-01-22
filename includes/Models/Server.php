<?php
namespace App\Models;

use App\Database;

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

    public static function create($name, $ip, $port, $smsService = '')
    {
        /** @var Database $db */
        $db = app()->make(Database::class);

        $db->query($db->prepare(
            "INSERT INTO `" . TABLE_PREFIX . "servers` " .
            "SET `name`='%s', `ip`='%s', `port`='%s', `sms_service`='%s'",
            [$name, $ip, $port, $smsService]
        ));

        $id = $db->last_id();

        return new Server($id, $name, $ip, $port, $smsService);
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