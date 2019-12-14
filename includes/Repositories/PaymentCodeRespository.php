<?php
namespace App\Repositories;

use App\Models\PaymentCode;
use App\System\Database;

class PaymentCodeRespository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($code, $ip, $platform)
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_code` " .
                    "SET `code` = '%s', `ip` = '%s', `platform` = '%s'",
                [$code, $ip, $platform]
            )
        );

        return $this->get($this->db->lastId());
    }

    public function get($id)
    {
        if ($id) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "payment_code` WHERE `id` = '%d'",
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
        return new PaymentCode(intval($data['id']), $data['code'], $data['ip'], $data['platform']);
    }
}
