<?php
namespace App\Repositories;

use App\Support\Database;

class UserServiceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $serviceId
     * @param int|null $seconds
     * @param int|null $userId
     * @param string|null $comment
     * @return int
     */
    public function create($serviceId, $seconds, $userId, $comment): int
    {
        $statement = $this->db->statement(
            "INSERT INTO `ss_user_service` (`service_id`, `expire`, `user_id`, `comment`) " .
                "VALUES (?, IF(? IS NULL, '-1', UNIX_TIMESTAMP() + ?), ?, ?)"
        );
        $statement->execute([$serviceId, $seconds, $seconds, $userId ?: 0, $comment ?: ""]);
        return $this->db->lastId();
    }

    /**
     * @param string $serviceId
     * @param int $expiresAt
     * @param int|null $userId
     * @param string|null $comment
     * @return int
     */
    public function createFixedExpire($serviceId, $expiresAt, $userId, $comment): int
    {
        $statement = $this->db->statement(
            "INSERT INTO `ss_user_service` (`service_id`, `expire`, `user_id`, `comment`) " .
                "VALUES (?, ?, ?, ?)"
        );
        $statement->execute([$serviceId, $expiresAt, $userId ?: 0, $comment ?: ""]);
        return $this->db->lastId();
    }

    public function delete($id): bool
    {
        $statement = $this->db->statement("DELETE FROM `ss_user_service` WHERE `id` = ?");
        $statement->execute([$id]);
        return !!$statement->rowCount();
    }

    public function deleteMany(array $ids): bool
    {
        if (!$ids) {
            return false;
        }

        $keys = implode(",", array_fill(0, count($ids), "?"));
        $statement = $this->db->statement("DELETE FROM `ss_user_service` WHERE `id` IN ({$keys})");
        $statement->execute($ids);

        return !!$statement->rowCount();
    }

    public function update($id, array $data): int
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

        [$params, $values] = map_to_params($data, false);
        $params = implode(", ", $params);

        $statement = $this->db->statement("UPDATE `ss_user_service` SET {$params} WHERE `id` = ?");
        $statement->execute(array_merge($values, [$id]));

        return $statement->rowCount();
    }

    public function updateWithModule($table, $userServiceId, array $data): int
    {
        $baseData = collect($data)->filter(
            fn($value, $key) => in_array($key, ["user_id", "service_id", "expire", "comment"], true)
        );

        $moduleData = collect($data)->filter(
            fn($value, $key) => !in_array($key, ["user_id", "expire", "comment"], true)
        );

        $affected = $this->update($userServiceId, $baseData->all());

        if ($moduleData->isPopulated()) {
            [$params, $values] = map_to_params($moduleData, false);
            $params = implode(", ", $params);

            $statement = $this->db->statement("UPDATE `$table` SET {$params} WHERE `us_id` = ?");
            $statement->execute(array_merge($values, [$userServiceId]));
            $affected = max($affected, $statement->rowCount());
        }

        return $affected;
    }

    public function updateUserId($id, $userId): bool
    {
        $statement = $this->db->statement(
            "UPDATE `ss_user_service` SET `user_id` = ? WHERE `id` = ?"
        );
        $statement->execute([$userId, $id]);

        return !!$statement->rowCount();
    }
}
