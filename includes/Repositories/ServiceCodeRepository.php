<?php
namespace App\Repositories;

use App\Models\ServiceCode;
use App\Support\Database;

class ServiceCodeRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($code, $serviceId, $priceId, $serverId = null, $uid = null)
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_service_codes` " .
                    "SET `code` = ?, `service` = ?, `price` = ?, `server` = ?, `uid` = ?"
            )
            ->execute([$code, $serviceId, $priceId, $serverId, $uid]);

        return $this->get($this->db->lastId());
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_service_codes` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_service_codes` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function mapToModel(array $data)
    {
        return new ServiceCode(
            as_int($data['id']),
            $data['code'],
            $data['service'],
            as_int($data['price']),
            as_int($data['server']),
            as_int($data['uid']),
            $data['timestamp']
        );
    }
}
