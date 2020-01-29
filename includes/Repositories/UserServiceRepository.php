<?php
namespace App\Repositories;

use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\Support\Database;

class UserServiceRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
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
        $statement = $this->db->statement("UPDATE `ss_user_service` SET `uid` = ? WHERE `id` = ?");
        $statement->execute([$uid, $id]);

        return !!$statement->rowCount();
    }
}
