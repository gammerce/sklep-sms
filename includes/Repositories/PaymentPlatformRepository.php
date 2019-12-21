<?php
namespace App\Repositories;

use App\Models\PaymentPlatform;
use App\System\Database;

class PaymentPlatformRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($name, $platform, array $data = [])
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_platforms` " .
                    "SET `name` = '%s', `platform` = '%s', `data` = '%s'",
                [$name, $platform, json_encode($data)]
            )
        );

        return $this->get($this->db->lastId());
    }

    public function get($id)
    {
        if ($id) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "payment_platforms` WHERE `id` = '%d'",
                    [$id]
                )
            );

            if ($data = $this->db->fetchArrayAssoc($result)) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    private function mapToModel(array $data)
    {
        return new PaymentPlatform(
            intval($data['id']),
            $data['name'],
            $data['platform'],
            json_decode($data['data'], true)
        );
    }
}
