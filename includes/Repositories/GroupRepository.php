<?php
namespace App\Repositories;

use App\Support\Database;

class GroupRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getFields()
    {
        $statement = $this->db->query("DESCRIBE ss_groups");

        return collect($statement)
            ->map(function (array $row) {
                return $row["Field"];
            })
            ->all();
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_groups` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function create($name, array $data)
    {
        $data["name"] = $name;

        $params = map_to_params($data);
        $values = map_to_values($data);

        $statement = $this->db->statement("INSERT INTO `ss_groups` SET {$params}");
        $statement->execute($values);

        return $this->db->lastId();
    }

    public function update($id, array $data)
    {
        if (!$data) {
            return false;
        }

        $params = map_to_params($data);
        $values = map_to_values($data);

        $statement = $this->db->statement("UPDATE `ss_groups` SET {$params} WHERE `id` = ?");
        $statement->execute(array_merge($values, [$id]));

        return !!$statement->rowCount();
    }
}
