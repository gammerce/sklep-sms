<?php
namespace App\Managers;

use App\Models\ServerService;
use App\Repositories\ServerServiceRepository;

class ServerServiceManager
{
    private ServerServiceRepository $serverServiceRepository;

    /** @var ServerService[] */
    private array $serversServices = [];
    private bool $serversServicesFetched = false;

    public function __construct(ServerServiceRepository $serverServiceRepository)
    {
        $this->serverServiceRepository = $serverServiceRepository;
    }

    /**
     * Checks if the service can be purchased on the given server
     *
     * @param int $serverId
     * @param string $serviceId
     * @return bool
     */
    public function serverServiceLinked($serverId, $serviceId): bool
    {
        if (!$this->serversServicesFetched) {
            $this->fetchServersServices();
        }

        return isset($this->serversServices[$serverId][$serviceId]);
    }

    private function fetchServersServices(): void
    {
        foreach ($this->serverServiceRepository->all() as $serverService) {
            $this->serversServices[$serverService->getServerId()][
                $serverService->getServiceId()
            ] = true;
        }

        $this->serversServicesFetched = true;
    }
}
