<?php
namespace App\Repositories;

use App\ServiceModules\ServiceModule;
use App\Support\Database;

class UserServiceRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $serviceId
     * @param int|null $seconds
     * @param int|null $userId
     * @return string
     */
    public function create($serviceId, $seconds, $userId)
    {
        $statement = $this->db->statement(
            "INSERT INTO `ss_user_service` (`service_id`, `expire`, `user_id`) " .
                "VALUES (?, IF(? IS NULL, '-1', UNIX_TIMESTAMP() + ?), ?)"
        );
        $statement->execute([$serviceId, $seconds, $seconds, $userId ?: 0]);
        return $this->db->lastId();
    }

    public function createFixedExpire($serviceId, $expiresAt, $userId)
    {
        $statement = $this->db->statement(
            "INSERT INTO `ss_user_service` (`service_id`, `expire`, `user_id`) " .
                "VALUES (?, ?, ?)"
        );
        $statement->execute([$serviceId, $expiresAt, $userId ?: 0]);
        return $this->db->lastId();
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

    public function update($id, array $data)
    {
        if (!$data) {
            return 0;
        }

        if (array_key_exists("user_id", $data) && $data["user_id"] === null) {
            $data["user_id"] = 0;
        }

        if (array_key_exists("expire", $data) && $data["expire"] === null) {
            $data["expire"] = -1;
        }

        $params = map_to_params($data);
        $values = map_to_values($data);

        $statement = $this->db->statement("UPDATE `ss_user_service` SET {$params} WHERE `id` = ?");
        $statement->execute(array_merge($values, [$id]));

        return $statement->rowCount();
    }

    public function updateWithModule(ServiceModule $serviceModule, $userServiceId, array $data)
    {
        $baseData = collect($data)->filter(function ($value, $key) {
            return in_array($key, ["user_id", "service_id", "expire"], true);
        });

        $moduleData = collect($data)->filter(function ($value, $key) {
            return !in_array($key, ["user_id", "expire"], true);
        });

        $affected = $this->update($userServiceId, $baseData->all());

        if ($moduleData->isPopulated()) {
            $params = map_to_params($moduleData);
            $values = map_to_values($moduleData);

            $table = $serviceModule::USER_SERVICE_TABLE;
            $statement = $this->db->statement("UPDATE `$table` SET {$params} WHERE `us_id` = ?");
            $statement->execute(array_merge($values, [$userServiceId]));
            $affected = max($affected, $statement->rowCount());
        }

        return $affected;
    }

    public function updateUserId($id, $userId)
    {
        $statement = $this->db->statement(
            "UPDATE `ss_user_service` SET `user_id` = ? WHERE `id` = ?"
        );
        $statement->execute([$userId, $id]);

        return !!$statement->rowCount();
    }
}
