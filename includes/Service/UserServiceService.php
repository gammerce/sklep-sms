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
     * @param string $query
     * @param array $values
     * @return UserService[]
     */
    public function find($query = "", array $values = []): array
    {
        $output = [];

        foreach ($this->serviceModuleManager->all() as $serviceModule) {
            $table = $serviceModule::USER_SERVICE_TABLE;

            if (!strlen($table)) {
                continue;
            }

            $statement = $this->db->statement(
                "SELECT * " .
                    "FROM `ss_user_service` AS us " .
                    "INNER JOIN `$table` AS m ON m.us_id = us.id " .
                    $query .
                    " ORDER BY us.id DESC "
            );
            $statement->execute($values);

            foreach ($statement as $row) {
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
        $userServices = $this->find("WHERE `id` = ?", [$userServiceId]);
        return $userServices ? $userServices[0] : null;
    }
}
