<?php
namespace App\Services;

use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\Models\User;
use App\System\Heart;

class ServiceListService
{
    /** @var Heart */
    private $heart;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    public function __construct(
        Heart $heart,
        ServiceModuleManager $serviceModuleManager,
        UserServiceAccessService $userServiceAccessService
    ) {
        $this->heart = $heart;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->userServiceAccessService = $userServiceAccessService;
    }

    /**
     * @param User $user
     * @return Service[]
     */
    public function getWebSupportedForUser(User $user)
    {
        return collect($this->heart->getServices())
            ->filter(function (Service $service) use ($user) {
                $serviceModule = $this->serviceModuleManager->get($service->getId());
                return $serviceModule &&
                    $serviceModule->showOnWeb() &&
                    $this->userServiceAccessService->canUserUseService($service, $user);
            })
            ->all();
    }
}
