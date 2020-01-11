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
        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "service_codes` " .
                    "SET `code` = ?, `service` = ?, `uid` = ?, `server` = ?, `amount` = ?, `tariff` = ?, `data` = ?"
            )
            ->execute([$code, $serviceId, $uid, $serverId, $amount, $tariff, $data]);

        return $this->get($this->db->lastId());
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT * FROM `" . TABLE_PREFIX . "service_codes` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function delete($id)
    {
        $statement = $this->db->statement(
            "DELETE FROM `" . TABLE_PREFIX . "service_codes` WHERE `id` = ?"
        );
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    private function mapToModel(array $data)
    {
        return new ServiceCode(
            (int) $data['id'],
            $data['code'],
            $data['service'],
            (int) $data['server'],
            (int) $data['tariff'],
            (int) $data['uid'],
            $data['amount'],
            $data['data'],
            $data['timestamp']
        );
    }
}
