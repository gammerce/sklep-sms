<?php
namespace App\Services;

use App\Loggers\DatabaseLogger;
use App\Repositories\UserServiceRepository;
use App\Support\Database;
use App\Managers\ServiceModuleManager;

class ExpiredUserServiceService
{
    /** @var Database */
    private $db;

    /** @var DatabaseLogger */
    private $logger;

    /** @var UserServiceService */
    private $userServiceService;

    /** @var UserServiceRepository */
    private $userServiceRepository;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Database $db,
        ServiceModuleManager $serviceModuleManager,
        DatabaseLogger $logger,
        UserServiceService $userServiceService,
        UserServiceRepository $userServiceRepository
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->userServiceService = $userServiceService;
        $this->userServiceRepository = $userServiceRepository;
        $this->serviceModuleManager = $serviceModuleManager;
    }

    public function deleteExpired()
    {
        // Usunięcie przestarzałych usług użytkownika
        // Pierwsze pobieramy te, które usuniemy
        // Potem wywolujemy akcje na module, potem je usuwamy, a następnie wywołujemy akcje na module

        $deleteIds = $usersServices = [];
        foreach (
            $this->userServiceService->find(
                "WHERE `expire` != '-1' AND `expire` < UNIX_TIMESTAMP()"
            )
            as $userService
        ) {
            $serviceModule = $this->serviceModuleManager->get($userService->getServiceId());
            if (!$serviceModule) {
                continue;
            }

            if ($serviceModule->userServiceDelete($userService, 'task')) {
                $deleteIds[] = $userService->getId();
                $usersServices[] = $userService;

                $this->logger->log(
                    'log_expired_service_delete',
                    "id: {$userService->getId()}, service_id: {$userService->getServiceId()}, uid: {$userService->getUid()}"
                );
            }
        }

        $this->userServiceRepository->deleteMany($deleteIds);

        foreach ($usersServices as $userService) {
            $serviceModule = $this->serviceModuleManager->get($userService->getServiceId());
            if ($serviceModule) {
                $serviceModule->userServiceDeletePost($userService);
            }
        }
    }
}
