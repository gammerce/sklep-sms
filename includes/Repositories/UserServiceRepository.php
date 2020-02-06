<?php
namespace App\Repositories;

use App\ServiceModules\ServiceModule;
use App\Support\Database;
use App\Support\Expression;

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
     * @param int|null $uid
     * @return string
     */
    public function create($serviceId, $seconds, $uid)
    {
        $statement = $this->db->statement(
            "INSERT INTO `ss_user_service` (`service`, `expire`, `uid`) " .
                "VALUES (?, IF(? IS NULL, '-1', UNIX_TIMESTAMP() + ?), ?)"
        );
        $statement->execute([$serviceId, $seconds, $seconds, $uid ?: 0]);
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
        $params = collect($data)
            ->map(function ($value, $key) {
                if ($value instanceof Expression) {
                    return "`$key` = $value";
                }

                return "`$key` = ?";
            })
            ->join(", ");

        $values = collect($data)
            ->values()
            ->filter(function ($value) {
                return !($value instanceof Expression);
            })
            ->all();

        $statement = $this->db->statement("UPDATE `ss_user_service` SET {$params} WHERE `id` = ?");
        $statement->execute(array_merge($values, [$id]));

        return $statement->rowCount();
    }

    public function updateWithModule(ServiceModule $serviceModule, $id, array $data)
    {
        $baseData = collect($data)->filter(function ($value, $key) {
            return in_array($key, ['uid', 'service', 'expire'], true);
        });

        $moduleData = collect($data)->filter(function ($value, $key) {
            return !in_array($key, ['uid', 'expire'], true);
        });

        $affected = $this->update($id, $baseData->all());

        if ($moduleData) {
            $params = $moduleData
                ->map(function ($value, $key) {
                    if ($value instanceof Expression) {
                        return "`$key` = $value";
                    }

                    return "`$key` = ?";
                })
                ->join(", ");

            $values = $moduleData
                ->values()
                ->filter(function ($value) {
                    return !($value instanceof Expression);
                })
                ->all();

            $table = $serviceModule::USER_SERVICE_TABLE;
            $statement = $this->db->statement("UPDATE `$table` SET {$params} WHERE `us_id` = ?");
            $statement->execute(array_merge($values, [$id]));
            $affected = max($affected, $statement->rowCount());
        }

        return $affected;
    }

    public function updateUid($id, $uid)
    {
        $statement = $this->db->statement("UPDATE `ss_user_service` SET `uid` = ? WHERE `id` = ?");
        $statement->execute([$uid, $id]);

        return !!$statement->rowCount();
    }
}
