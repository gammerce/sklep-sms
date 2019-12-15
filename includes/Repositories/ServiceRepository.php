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
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $services[] = $this->mapToModel($row);
        }

        return $services;
    }

    public function get($id)
    {
        if ($id) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "services` WHERE `id` = '%s'",
                    [$id]
                )
            );

            if ($data = $this->db->fetchArrayAssoc($result)) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function delete($id)
    {
        $this->db->query(
            $this->db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "services` " . "WHERE `id` = '%s'",
                [$id]
            )
        );

        return $this->db->affectedRows();
    }

    private function mapToModel(array $data)
    {
        return new Service(
            $data['id'],
            $data['name'],
            $data['shortDescription'],
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
