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
    public function all()
    {
        if (!$this->serversFetched) {
            $this->fetch();
        }

        return $this->servers;
    }

    /**
     * @param int $id
     * @return Server|null
     */
    public function get($id)
    {
        return array_get($this->all(), $id, null);
    }

    public function getCount()
    {
        return count($this->all());
    }

    private function fetch()
    {
        foreach ($this->serverRepository->all() as $server) {
            $this->servers[$server->getId()] = $server;
        }

        $this->serversFetched = true;
    }
}
