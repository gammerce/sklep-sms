<?php
namespace App\Models;

use App\Database;

class ServerService
{
    /** @var int */
    private $serverId;

    /** @var string */
    private $serviceId;

    public function __construct($serverId, $serviceId)
    {
        $this->serverId = $serverId;
        $this->serviceId = $serviceId;
    }

    public static function create($serverId, $serviceId)
    {
        /** @var Database $db */
        $db = app()->make(Database::class);

        $db->query($db->prepare(
            "INSERT INTO `" . TABLE_PREFIX . "servers_services` " .
            "SET `server_id`='%d', `service_id`='%s'",
            [$serverId, $serviceId]
        ));

        return new ServerService($serverId, $serviceId);
    }

    public function getServerId()
    {
        return $this->serverId;
    }

    public function getServiceId()
    {
        return $this->serviceId;
    }
}