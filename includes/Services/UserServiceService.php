<?php
namespace App\Services;

use App\Managers\ServiceModuleManager;
use App\Models\UserService;
use App\Repositories\UserServiceRepository;
use App\Support\Database;

class UserServiceService
{
    /** @var Database */
    private $db;

    /** @var UserServiceRepository */
    private $userServiceRepository;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

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
    public function find($conditions = "")
    {
        if (my_is_integer($conditions)) {
            $conditions = "WHERE `id` = " . intval($conditions);
        }

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
     * @param string $conditions
     * @return UserService|null
     */
    public function findOne($conditions = "")
    {
        $userServices = $this->find($conditions);
        return $userServices ? $userServices[0] : null;
    }
}
