<?php
namespace App\Repositories;

use App\Models\Service;
use App\Support\Database;

class ServiceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return Service[]
     */
    public function all()
    {
        $statement = $this->db->query("SELECT * FROM `ss_services` ORDER BY `order` ASC");

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_services` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function findMany(array $ids)
    {
        if (!$ids) {
            return [];
        }

        $keys = implode(",", array_fill(0, count($ids), "?"));
        $statement = $this->db->statement(
            "SELECT * FROM `ss_services` WHERE `id` IN ({$keys}) ORDER BY `order` ASC"
        );
        $statement->execute($ids);

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    public function create(
        $id,
        $name,
        $shortDescription,
        $description,
        $tag,
        $module,
        array $groups = [],
        $order = 1,
        array $data = [],
        $types = 0,
        $flags = ""
    ) {
        $this->db
            ->statement(
                "INSERT INTO `ss_services` " .
                    "SET `id` = ?, `name` = ?, `short_description` = ?, `description` = ?, `tag` = ?, " .
                    "`module` = ?, `groups` = ?, `order` = ?, `data` = ?, `types` = ?, `flags` = ?"
            )
            ->execute([
                $id,
                $name,
                $shortDescription ?: "",
                $description ?: "",
                $tag ?: "",
                $module,
                implode(";", $groups),
                $order,
                json_encode($data),
                $types,
                strtolower($flags),
            ]);

        return $this->get($id);
    }

    public function update(
        $id,
        $newId,
        $name,
        $shortDescription,
        $description,
        $tag,
        array $groups,
        $order,
        array $data,
        $types,
        $flags
    ) {
        $statement = $this->db->statement(
            "UPDATE `ss_services` " .
                "SET `id` = ?, `name` = ?, `short_description` = ?, `description` = ?, `tag` = ?, " .
                "`groups` = ?, `order` = ?, `data` = ?, `types` = ?, `flags` = ? " .
                "WHERE `id` = ?"
        );
        $statement->execute([
            $newId,
            $name,
            $shortDescription ?: "",
            $description ?: "",
            $tag ?: "",
            implode(";", $groups),
            $order,
            json_encode($data),
            $types,
            strtolower($flags),
            $id,
        ]);

        return !!$statement->rowCount();
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_services` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    private function mapToModel(array $data)
    {
        return new Service(
            $data["id"],
            $data["name"],
            $data["short_description"],
            $data["description"],
            $data["types"],
            $data["tag"],
            $data["module"],
            explode_int_list($data["groups"], ";"),
            strtolower($data["flags"]),
            $data["order"],
            json_decode($data["data"], true)
        );
    }
}
