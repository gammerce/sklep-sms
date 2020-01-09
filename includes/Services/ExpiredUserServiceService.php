<?php
namespace App\Services;

use App\Loggers\DatabaseLogger;
use App\System\Database;
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

    public function __construct(Database $db, Heart $heart, DatabaseLogger $logger,         UserServiceService $userServiceService)
    {
        $this->db = $db;
        $this->heart = $heart;
        $this->logger = $logger;
        $this->userServiceService = $userServiceService;
    }

    public function deleteExpiredUserServices()
    {
        // Usunięcie przestarzałych usług użytkownika
        // Pierwsze pobieramy te, które usuniemy
        // Potem wywolujemy akcje na module, potem je usuwamy, a następnie wywołujemy akcje na module

        $deleteIds = $usersServices = [];
        foreach (
            $this->userServiceService->find("WHERE `expire` != '-1' AND `expire` < UNIX_TIMESTAMP()")
            as $userService
        ) {
            if (
                ($serviceModule = $this->heart->getServiceModule($userService['service'])) === null
            ) {
                continue;
            }

            if ($serviceModule->userServiceDelete($userService, 'task')) {
                $deleteIds[] = $userService['id'];
                $usersServices[] = $userService;

                $userServiceDesc = '';
                foreach ($userService as $key => $value) {
                    if (strlen($userServiceDesc)) {
                        $userServiceDesc .= ' ; ';
                    }

                    $userServiceDesc .= ucfirst(strtolower($key)) . ': ' . $value;
                }

                $this->logger->log('expired_service_delete', $userServiceDesc);
            }
        }

        // Usuwamy usugi ktre zwróciły true
        if (!empty($deleteIds)) {
            $this->db->query(
                "DELETE FROM `" .
                    TABLE_PREFIX .
                    "user_service` " .
                    "WHERE `id` IN (" .
                    implode(", ", $deleteIds) .
                    ")"
            );
        }

        // Wywołujemy akcje po usunieciu
        foreach ($usersServices as $userService) {
            if (
                ($serviceModule = $this->heart->getServiceModule($userService['service'])) === null
            ) {
                continue;
            }

            $serviceModule->userServiceDeletePost($userService);
        }
    }
}
