<?php
namespace App\Service;

use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\Models\User;

class ServiceListService
{
    private ServiceModuleManager $serviceModuleManager;
    private UserServiceAccessService $userServiceAccessService;
    private ServiceManager $serviceManager;

    public function __construct(
        ServiceManager $serviceManager,
        ServiceModuleManager $serviceModuleManager,
        UserServiceAccessService $userServiceAccessService
    ) {
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
}
