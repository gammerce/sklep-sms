<?php
namespace App\Service;

use App\Managers\ServiceModuleManager;
use App\Models\UserService;
use App\Repositories\UserServiceRepository;
use App\Support\Database;

class UserServiceService
{
    private Database $db;
    private UserServiceRepository $userServiceRepository;
    private ServiceModuleManager $serviceModuleManager;

    public function __construct(
        ServiceModuleManager $serviceModuleManager,
        Database $db,
        UserServiceRepository $userServiceRepository
    ) {
        $this->db = $db;
        $this->userServiceRepository = $userServiceRepository;
        $this->serviceModuleManager = $serviceModuleManager;
    }

    /**
     * @param string|int $conditions
     * @return UserService[]
     */
    public function find($conditions = ""): array
    {
        $output = [];

        foreach ($this->serviceModuleManager->all() as $serviceModule) {
            $table = $serviceModule::USER_SERVICE_TABLE;

            if (!strlen($table)) {
                continue;
            }

            $result = $this->db->query(
                "SELECT * " .
                    "FROM `ss_user_service` AS us " .
                    "INNER JOIN `$table` AS m ON m.us_id = us.id " .
                    $conditions .
                    " ORDER BY us.id DESC "
            );

            foreach ($result as $row) {
                $userService = $serviceModule->mapToUserService($row);
                $output[$userService->getId()] = $userService;
            }
        }

        ksort($output);
        $output = array_reverse($output);

        return $output;
    }

    /**
     * @param int $userServiceId
     * @return UserService|null
     */
    public function findOne($userServiceId): ?UserService
    {
        $userServices = $this->find("WHERE `id` = " . intval($userServiceId));
        return $userServices ? $userServices[0] : null;
    }
}
