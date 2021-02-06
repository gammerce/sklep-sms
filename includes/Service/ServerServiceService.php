<?php
namespace App\Service;

use App\Support\Database;

class ServerServiceService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function updateLinks(array $data): void
    {
        $itemsToCreate = collect($data)->filter(fn(array $item) => $item["connect"]);
        $itemsToDelete = collect($data)->filter(fn(array $item) => !$item["connect"]);

        if ($itemsToCreate->isPopulated()) {
            $keys = $itemsToCreate->map(fn() => "(?, ?)")->join(", ");

            $values = $itemsToCreate
                ->flatMap(fn(array $item) => [$item["server_id"], $item["service_id"]])
                ->all();

            $this->db
                ->statement(
                    "INSERT IGNORE INTO `ss_servers_services` (`server_id`, `service_id`) " .
                        "VALUES {$keys}"
                )
                ->execute($values);
        }

        if ($itemsToDelete->isPopulated()) {
            $keys = $itemsToDelete
                ->map(fn() => "(`server_id` = ? AND `service_id` = ?)")
                ->join(" OR ");

            $values = $itemsToDelete
                ->flatMap(fn(array $item) => [$item["server_id"], $item["service_id"]])
                ->all();

            $this->db
                ->statement("DELETE FROM `ss_servers_services` WHERE {$keys}")
                ->execute($values);
        }
    }
}
