<?php
namespace App\Repositories;

use App\Models\ServiceCode;
use App\System\Database;

class ServiceCodeRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(
        $code,
        $serviceId,
        $uid = 0,
        $serverId = 0,
        $amount = 0,
        $tariff = 0,
        $data = ""
    ) {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "service_codes` " .
                    "SET `code` = '%s', `service` = '%s', `uid` = '%d', `server` = '%d', `amount` = '%d', `tariff` = '%d', `data` = '%s'",
                [$code, $serviceId, $uid, $serverId, $amount, $tariff, $data]
            )
        );

        return $this->get($this->db->lastId());
    }

    public function get($id)
    {
        if ($id) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "service_codes` WHERE `id` = '%d'",
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
        $statement = $this->db->query(
            $this->db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "service_codes` " . "WHERE `id` = '%d'",
                [$id]
            )
        );

        return !!$statement->rowCount();
    }

    private function mapToModel(array $data)
    {
        return new ServiceCode(
            intval($data['id']),
            $data['code'],
            $data['service'],
            intval($data['server']),
            intval($data['tariff']),
            intval($data['uid']),
            $data['amount'],
            $data['data'],
            $data['timestamp']
        );
    }
}
