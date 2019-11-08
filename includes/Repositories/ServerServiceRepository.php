<?php
namespace App\Repositories;

use App\System\Database;
use App\Models\ServerService;

class ServerServiceRepository
{
    /** @var Database */
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($serverId, $serviceId)
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "servers_services` " .
                    "SET `server_id`='%d', `service_id`='%s'",
                [$serverId, $serviceId]
            )
        );

        return new ServerService($serverId, $serviceId);
    }
}
