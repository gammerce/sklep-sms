<?php
namespace App\Repositories;

use App\Models\Service;
use App\System\Database;

class ServiceRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return Service[]
     */
    public function all()
    {
        $result = $this->db->query(
            "SELECT * FROM `" . TABLE_PREFIX . "services` ORDER BY `order` ASC"
        );

        $services = [];
        foreach ($result as $row) {
            $services[] = $this->mapToModel($row);
        }

        return $services;
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT * FROM `" . TABLE_PREFIX . "services` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
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
        $flags = ''
    ) {
        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "services` " .
                    "SET `id` = ?, `name` = ?, `short_description` = ?, `description` = ?, `tag` = ?, " .
                    "`module` = ?, `groups` = ?, `order` = ?, `data` = ?, `types` = ?, `flags` = ?"
            )
            ->execute([
                $id,
                $name,
                $shortDescription,
                $description,
                $tag,
                $module,
                implode(";", $groups),
                $order,
                json_encode($data),
                $types,
                $flags,
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
            "UPDATE `" .
                TABLE_PREFIX .
                "services` " .
                "SET `id` = ?, `name` = ?, `short_description` = ?, `description` = ?, `tag` = ?, " .
                "`groups` = ?, `order` = ?, `data` = ?, `types` = ?, `flags` = ? " .
                "WHERE `id` = ?"
        );
        $statement->execute([
            $newId,
            $name,
            $shortDescription,
            $description,
            $tag,
            implode(";", $groups),
            $order,
            json_encode($data),
            $types,
            $flags,
            $id,
        ]);

        return !!$statement->rowCount();
    }

    public function delete($id)
    {
        $statement = $this->db->statement(
            "DELETE FROM `" . TABLE_PREFIX . "services` " . "WHERE `id` = ?"
        );
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    private function mapToModel(array $data)
    {
        return new Service(
            $data['id'],
            $data['name'],
            $data['short_description'],
            $data['description'],
            $data['types'],
            $data['tag'],
            $data['module'],
            $data['groups'] ? explode(";", $data['groups']) : [],
            $data['flags'],
            $data['order'],
            json_decode($data['data'], true)
        );
    }
}
