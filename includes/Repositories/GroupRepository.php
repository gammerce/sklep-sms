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

    public function getPermissions(): array
    {
        return Permission::values();
    }

    /**
     * @return Group[]
     */
    public function all(): array
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
    public function get($id): ?Group
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_groups` WHERE `id` = ?");
            $statement->bindAndExecute([$id]);

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
    public function delete($id): bool
    {
        $statement = $this->db->statement("DELETE FROM `ss_groups` WHERE `id` = ?");
        $statement->bindAndExecute([$id]);
        return !!$statement->rowCount();
    }

    /**
     * @param string $name
     * @param Permission[] $permissions
     * @return Group
     */
    public function create($name, array $permissions): Group
    {
        $statement = $this->db->statement(
            "INSERT INTO `ss_groups` SET `name` = ?, `permissions` = ?"
        );
        $statement->bindAndExecute([$name, implode(",", $permissions)]);
        return $this->get($this->db->lastId());
    }

    /**
     * @param int $id
     * @param string $name
     * @param Permission[] $permissions
     * @return bool
     */
    public function update($id, $name, array $permissions): bool
    {
        $statement = $this->db->statement(
            "UPDATE `ss_groups` SET `name` = ?, `permissions` = ? WHERE `id` = ?"
        );
        $statement->bindAndExecute([$name, implode(",", $permissions), $id]);
        return !!$statement->rowCount();
    }

    private function mapToModel(array $data): Group
    {
        $permissions = as_permission_list(explode(",", $data["permissions"]) ?: []);
        return new Group(as_int($data["id"]), as_string($data["name"]), $permissions);
    }
}
