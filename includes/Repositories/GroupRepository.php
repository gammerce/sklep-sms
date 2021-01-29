<?php
namespace App\Repositories;

use App\Models\Group;
use App\Support\Database;
use App\User\Permission;

class GroupRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getPermissions()
    {
        return Permission::values();
    }

    /**
     * @return Group[]
     */
    public function all()
    {
        $statement = $this->db->query("SELECT * FROM `ss_groups`");

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    /**
     * @param int $id
     * @return Group|null
     */
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

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_groups` WHERE `id` = ?");
        $statement->execute([$id]);
        return !!$statement->rowCount();
    }

    /**
     * @param string $name
     * @param Permission[] $permissions
     * @return Group
     */
    public function create($name, array $permissions)
    {
        $statement = $this->db->statement(
            "INSERT INTO `ss_groups` SET `name` = ?, `permissions` = ?"
        );
        $statement->execute([$name, implode(",", $permissions)]);
        return $this->get($this->db->lastId());
    }

    /**
     * @param int $id
     * @param string $name
     * @param Permission[] $permissions
     * @return bool
     */
    public function update($id, $name, array $permissions)
    {
        $statement = $this->db->statement(
            "UPDATE `ss_groups` SET `name` = ?, `permissions` = ? WHERE `id` = ?"
        );
        $statement->execute([$name, implode(",", $permissions), $id]);
        return !!$statement->rowCount();
    }

    /**
     * @param array $data
     * @return Group
     */
    private function mapToModel(array $data)
    {
        $permissions = as_permission_list(explode(",", $data["permissions"]) ?: []);
        return new Group(as_int($data["id"]), as_string($data["name"]), $permissions);
    }
}
