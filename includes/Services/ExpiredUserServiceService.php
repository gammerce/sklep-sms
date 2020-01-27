<?php
namespace App\Services;

use App\Loggers\DatabaseLogger;
use App\Repositories\UserServiceRepository;
use App\Support\Database;
use App\System\Heart;

class ExpiredUserServiceService
{
    /** @var Database */
    private $db;

    /** @var Heart */
    private $heart;

    /** @var DatabaseLogger */
    private $logger;

    /** @var UserServiceService */
    private $userServiceService;

    /** @var UserServiceRepository */
    private $userServiceRepository;

    public function __construct(
        Database $db,
        Heart $heart,
        DatabaseLogger $logger,
        UserServiceService $userServiceService,
        UserServiceRepository $userServiceRepository
    ) {
        $this->db = $db;
        $this->heart = $heart;
        $this->logger = $logger;
        $this->userServiceService = $userServiceService;
        $this->userServiceRepository = $userServiceRepository;
    }

    public function deleteExpiredUserServices()
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
            $serviceModule = $this->heart->getServiceModule($userService->getServiceId());
            if (!$serviceModule) {
                continue;
            }

            if ($serviceModule->userServiceDelete($userService, 'task')) {
                $deleteIds[] = $userService->getId();
                $usersServices[] = $userService;

                $this->logger->log(
                    'expired_service_delete',
                    "id: {$userService->getId()}, service_id: {$userService->getServiceId()}, uid: {$userService->getUid()}"
                );
            }
        }

        $this->userServiceRepository->deleteMany($deleteIds);

        foreach ($usersServices as $userService) {
            $serviceModule = $this->heart->getServiceModule($userService->getServiceId());
            if ($serviceModule) {
                $serviceModule->userServiceDeletePost($userService);
            }
        }
    }
}
