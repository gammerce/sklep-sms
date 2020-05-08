<?php
namespace App\Managers;

use App\Models\Server;
use App\Repositories\ServerRepository;

class ServerManager
{
    /** @var ServerRepository */
    private $serverRepository;

    /** @var Server[] */
    private $servers = [];
    private $serversFetched = false;

    public function __construct(ServerRepository $serverRepository)
    {
        $this->serverRepository = $serverRepository;
    }

    /**
     * @return Server[]
     */
    public function getServers()
    {
        if (!$this->serversFetched) {
            $this->fetchServers();
        }

        return $this->servers;
    }

    /**
     * @param int $id
     * @return Server|null
     */
    public function getServer($id)
    {
        return array_get($this->getServers(), $id, null);
    }

    public function getServersAmount()
    {
        return count($this->getServers());
    }

    private function fetchServers()
    {
        foreach ($this->serverRepository->all() as $server) {
            $this->servers[$server->getId()] = $server;
        }

        $this->serversFetched = true;
    }
}
