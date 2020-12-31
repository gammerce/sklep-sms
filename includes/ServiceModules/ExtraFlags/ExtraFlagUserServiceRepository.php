<?php
namespace App\ServiceModules\ExtraFlags;

use App\Exceptions\EntityNotFoundException;
use App\Repositories\UserServiceRepository;
use App\Support\Database;

class ExtraFlagUserServiceRepository
{
    /** @var Database */
    private $db;

    /** @var UserServiceRepository */
    private $userServiceRepository;

    public function __construct(Database $db, UserServiceRepository $userServiceRepository)
    {
        $this->db = $db;
        $this->userServiceRepository = $userServiceRepository;
    }

    public function find(array $data): ?ExtraFlagUserService
    {
        $models = $this->findAll($data);
        return empty($models) ? null : $models[0];
    }

    /**
     * @param array $data
     * @return ExtraFlagUserService
     * @throws EntityNotFoundException
     */
    public function findOrFail(array $data)
    {
        $models = $this->findAll($data);
        if (empty($models)) {
            throw new EntityNotFoundException();
        }

        return $models[0];
    }

    /**
     * @param array $data
     * @return ExtraFlagUserService[]
     */
    public function findAll(array $data)
    {
        list($params, $values) = map_to_params($data);
        $params = implode(" AND ", $params);

        $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;

        $statement = $this->db->statement(
            "SELECT * FROM `ss_user_service` AS us " .
                "INNER JOIN `$table` AS m ON m.us_id = us.id " .
                ($params ? "WHERE {$params}" : "")
        );
        $statement->execute($values);

        return collect($statement)
            ->map(function (array $row) {
                return $this->mapToModel($row);
            })
            ->all();
    }

    public function create($serviceId, $userId, $seconds, $serverId, $type, $authData, $password)
    {
        $userServiceId = $this->userServiceRepository->create($serviceId, $seconds, $userId);

        $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            "INSERT INTO `$table` (`us_id`, `server_id`, `service_id`, `type`, `auth_data`, `password`) " .
                "VALUES (?, ?, ?, ?, ?, ?)"
        );
        $statement->execute([$userServiceId, $serverId, $serviceId, $type, $authData, $password]);

        return $this->get($userServiceId);
    }

    public function get($id)
    {
        if ($id) {
            $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;
            $statement = $this->db->statement(
                "SELECT * FROM `ss_user_service` AS us " .
                    "INNER JOIN `$table` AS m ON m.us_id = us.id " .
                    "WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * @param string $password
     * @param int $serverId
     * @param int $type
     * @param string $authData
     */
    public function updatePassword($password, $serverId, $type, $authData)
    {
        $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;
        $this->db
            ->statement(
                <<<EOF
UPDATE `$table` 
SET `password` = ? 
WHERE `server_id` = ? AND `type` = ? AND `auth_data` = ?
EOF
            )
            ->execute([$password, $serverId, $type, $authData]);
    }

    public function mapToModel(array $data)
    {
        return new ExtraFlagUserService(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["user_id"]),
            as_int($data["expire"]),
            as_int($data["server_id"]),
            as_int($data["type"]),
            as_string($data["auth_data"]),
            as_string($data["password"])
        );
    }
}
