<?php
namespace App\Repositories;

use App\Models\ServerService;
use App\Support\Database;

class ServerServiceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return ServerService[]
     */
    public function all(): array
    {
        $statement = $this->db->query("SELECT * FROM `ss_servers_services`");

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    public function create($serverId, $serviceId): ServerService
    {
        $this->db
            ->statement("INSERT INTO `ss_servers_services` SET `server_id` = ?, `service_id` = ?")
            ->bindAndExecute([$serverId, $serviceId]);

        return $this->mapToModel([
            "server_id" => $serverId,
            "service_id" => $serviceId,
        ]);
    }

    /**
     * @param int $serverId
     * @return ServerService[]
     */
    public function findByServer($serverId): array
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_servers_services` WHERE `server_id` = ?"
        );
        $statement->bindAndExecute([$serverId]);

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    /**
     * @param string $serviceId
     * @return ServerService[]
     */
    public function findByService($serviceId): array
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_servers_services` WHERE `service_id` = ?"
        );
        $statement->bindAndExecute([$serviceId]);

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    public function mapToModel(array $data): ServerService
    {
        return new ServerService(as_int($data["server_id"]), $data["service_id"]);
    }
}
