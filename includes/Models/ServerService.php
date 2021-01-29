<?php
namespace App\Models;

class ServerService
{
    private int $serverId;
    private string $serviceId;

    public function __construct($serverId, $serviceId)
    {
        $this->serverId = $serverId;
        $this->serviceId = $serviceId;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }
}
