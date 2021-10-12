<?php
namespace App\Server;

use App\Managers\ServerManager;
use App\Models\Server;

class ServerListService
{
    private ServerManager $serverManager;

    public function __construct(ServerManager $serverManager)
    {
        $this->serverManager = $serverManager;
    }

    /**
     * @return Server[]
     */
    public function listActive(): array
    {
        return collect($this->serverManager->all())
            ->filter(fn(Server $server) => $server->isActive())
            ->all();
    }
}
