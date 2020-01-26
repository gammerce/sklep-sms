<?php
namespace App\Repositories;

use App\Support\Database;

class LogRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_logs` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function create($message)
    {
        $this->db->statement("INSERT INTO `ss_logs` SET `text` = ?")->execute([$message]);
    }
}
