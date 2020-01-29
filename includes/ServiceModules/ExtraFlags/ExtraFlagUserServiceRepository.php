<?php
namespace App\ServiceModules\ExtraFlags;

use App\Support\Database;

class ExtraFlagUserServiceRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(
        $serviceId,
        $uid,
        $forever,
        $days,
        $serverId,
        $type,
        $authData,
        $password
    ) {
        $statement = $this->db->statement(
            "INSERT INTO `ss_user_service` (`uid`, `service`, `expire`) " .
                "VALUES (?, ?, IF(? = '1', '-1', UNIX_TIMESTAMP() + ?)) "
        );
        $statement->execute([$uid ?: 0, $serviceId, $forever, $days * 24 * 60 * 60]);
        $userServiceId = $this->db->lastId();

        $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            "INSERT INTO `$table` (`us_id`, `server`, `service`, `type`, `auth_data`, `password`) " .
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
            as_int($data['id']),
            $data['service'],
            as_int($data['uid']),
            as_int($data['expire']),
            as_int($data['server']),
            as_int($data['type']),
            $data['auth_data'],
            $data['password']
        );
    }
}
