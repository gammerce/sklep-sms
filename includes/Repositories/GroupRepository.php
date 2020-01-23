<?php
namespace App\Repositories;

use App\System\Database;

class GroupRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_groups` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }
}
