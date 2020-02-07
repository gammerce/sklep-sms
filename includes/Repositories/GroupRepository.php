<?php
namespace App\Repositories;

use App\Models\Group;
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

    /**
     * @return Group[]
     */
    public function all()
    {
        $statement = $this->db->query("SELECT * FROM `ss_groups`");

        return collect($statement)
            ->map(function (array $row) {
                return $this->mapToModel($row);
            })
            ->all();
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_groups` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
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

        return $this->get($this->db->lastId());
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

    private function mapToModel(array $data)
    {
        $permissions = collect($data)
            ->filter(function ($value, $key) {
                return !in_array($key, ["id", "name"], true);
            })
            ->mapWithKeys(function ($value) {
                return !!$value;
            })
            ->all();

        return new Group(as_int($data['id']), $data['name'], $permissions);
    }
}
