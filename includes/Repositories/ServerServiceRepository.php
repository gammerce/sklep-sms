<?php
namespace App\Repositories;

use App\Models\ServerService;
use App\System\Database;

class ServerServiceRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($serverId, $serviceId)
    {
        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "servers_services` " .
                    "SET `server_id` = ?, `service_id` = ?"
            )
            ->execute([$serverId, $serviceId]);

        return $this->mapToModel($serverId, $serviceId);
    }

    /**
     * @param int $serverId
     * @return ServerService[]
     */
    public function findByServer($serverId)
    {
        $statement = $this->db->statement(
            "SELECT * FROM `" . TABLE_PREFIX . "servers_services` WHERE `server_id` = ?"
        );
        $statement->execute([$serverId]);

        $serverServices = [];
        foreach ($statement as $row) {
            $serverServices[] = $this->mapToModel($row['server_id'], $row['service_id']);
        }

        return $serverServices;
    }

    public function mapToModel($serverId, $serviceId)
    {
        return new ServerService((int) $serverId, $serviceId);
    }
}
