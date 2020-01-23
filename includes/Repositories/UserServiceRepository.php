<?php
namespace App\Repositories;

use App\Models\ExtraFlagsUserService;
use App\Models\MybbExtraGroupsUserService;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\System\Database;

class UserServiceRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function createExtraFlags(
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

        $table = "ss_" . ExtraFlagsServiceModule::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            "INSERT INTO `$table` (`us_id`, `server`, `service`, `type`, `auth_data`, `password`) " .
                "VALUES (?, ?, ?, ?, ?, ?)"
        );
        $statement->execute([
            $userServiceId,
            $serverId,
            $serviceId,
            $type,
            $authData,
            $password ?: '',
        ]);
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_user_service` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function deleteMany(array $ids)
    {
        if (!$ids) {
            return false;
        }

        $keys = implode(",", array_fill(0, count($ids), "?"));
        $statement = $this->db->statement("DELETE FROM `ss_user_service` WHERE `id` IN ({$keys})");
        $statement->execute($ids);

        return !!$statement->rowCount();
    }

    public function updateUid($id, $uid)
    {
        $statement = $this->db->statement(
            "UPDATE `ss_user_service` " . "SET `uid` = ? " . "WHERE `id` = ?"
        );
        $statement->execute([$uid, $id]);

        return !!$statement->rowCount();
    }
}
