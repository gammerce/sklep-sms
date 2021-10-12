<?php
namespace App\Service;

use App\Managers\ServerServiceManager;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\Server;
use App\Models\Service;
use App\Models\User;

class ServiceListService
{
    private ServiceModuleManager $serviceModuleManager;
    private UserServiceAccessService $userServiceAccessService;
    private ServiceManager $serviceManager;
    private ServerServiceManager $serverServiceManager;

    public function __construct(
        ServerServiceManager $serverServiceManager,
        ServiceManager $serviceManager,
        ServiceModuleManager $serviceModuleManager,
        UserServiceAccessService $userServiceAccessService
    ) {
        $this->serverServiceManager = $serverServiceManager;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->serviceManager = $serviceManager;
    }

    /**
     * @param User $user
     * @return Service[]
     */
    public function getWebSupportedForUser(User $user): array
    {
        return collect($this->serviceManager->all())
            ->filter(function (Service $service) use ($user) {
                $serviceModule = $this->serviceModuleManager->get($service->getId());
                return $serviceModule &&
                    $serviceModule->showOnWeb() &&
                    $this->userServiceAccessService->canUserUseService($service, $user);
            })
            ->all();
    }

    /**
     * @param User $user
     * @param Server $server
     * @return Service[]
     */
    public function getWebSupportedForUserAndServer(User $user, Server $server): array
    {
        return collect($this->getWebSupportedForUser($user))
            ->filter(
                fn(Service $service) => $this->serverServiceManager->serverServiceLinked(
                    $server->getId(),
                    $service->getId()
                )
            )
            ->all();
    }
}
