<?php
namespace App\Managers;

use App\Models\Server;
use App\Repositories\ServerRepository;

class ServerManager
{
    private ServerRepository $serverRepository;

    /** @var Server[] */
    private array $servers = [];
    private bool $serversFetched = false;

    public function __construct(ServerRepository $serverRepository)
    {
        $this->serverRepository = $serverRepository;
    }

    /**
     * @return Server[]
     */
    public function all(): array
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
    public function get($id): ?Server
    {
        return array_get($this->all(), $id, null);
    }

    public function getCount(): int
    {
        return count($this->all());
    }

    private function fetch(): void
    {
        foreach ($this->serverRepository->all() as $server) {
            $this->servers[$server->getId()] = $server;
        }

        $this->serversFetched = true;
    }
}
