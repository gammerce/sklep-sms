<?php
namespace App\Services;

use App\Models\UserService;
use App\Repositories\UserServiceRepository;
use App\System\Database;
use App\System\Heart;

class UserServiceService
{
    /** @var Heart */
    private $heart;

    /** @var Database */
    private $db;

    /** @var UserServiceRepository */
    private $userServiceRepository;

    public function __construct(
        Heart $heart,
        Database $db,
        UserServiceRepository $userServiceRepository
    ) {
        $this->heart = $heart;
        $this->db = $db;
        $this->userServiceRepository = $userServiceRepository;
    }

    /**
     * @param string|int $conditions
     * @return UserService[]
     */
    public function find($conditions = '')
    {
        if (my_is_integer($conditions)) {
            $conditions = "WHERE `id` = " . intval($conditions);
        }

        $output = [];

        foreach ($this->heart->getEmptyServiceModules() as $serviceModule) {
            $table = $serviceModule::USER_SERVICE_TABLE;

            if (!strlen($table)) {
                continue;
            }

            $result = $this->db->query(
                "SELECT * " .
                    "FROM `ss_user_service` AS us " .
                    "INNER JOIN `ss_$table` AS m ON m.us_id = us.id " .
                    $conditions .
                    " ORDER BY us.id DESC "
            );

            foreach ($result as $row) {
                $output[$row['id']] = $serviceModule->mapToUserService($row);
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
    public function findOne($conditions = '')
    {
        $userServices = $this->find($conditions);
        return $userServices ? $userServices[0] : null;
    }
}
