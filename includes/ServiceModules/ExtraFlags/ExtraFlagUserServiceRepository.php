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

    /**
     * @param array $data
     * @return ExtraFlagUserService
     * @throws EntityNotFoundException
     */
    public function findOrFail(array $data)
    {
        $params = map_to_where_params($data);
        $values = map_to_values($data);

        $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;

        $statement = $this->db->statement(
            "SELECT * FROM `ss_user_service` AS us " .
                "INNER JOIN `$table` AS m ON m.us_id = us.id " .
                ($params ? "WHERE {$params}" : "")
        );
        $statement->execute($values);

        $data = $statement->fetch();
        if (!$data) {
            throw new EntityNotFoundException();
        }

        return $this->mapToModel($data);
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
