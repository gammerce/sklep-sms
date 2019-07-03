<?php
namespace App\Models;

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

    public function getServerId()
    {
        return $this->serverId;
    }

    public function getServiceId()
    {
        return $this->serviceId;
    }
}
