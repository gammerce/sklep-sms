<?php
namespace App\Services;

use App\Support\Database;

class ServerServiceService
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function updateAffiliations(array $data)
    {
        $itemsToCreate = collect($data)->filter(function (array $item) {
            return $item["connect"];
        });

        $itemsToDelete = collect($data)->filter(function (array $item) {
            return !$item["connect"];
        });

        if ($itemsToCreate->isPopulated()) {
            $keys = $itemsToCreate
                ->map(function () {
                    return "(?, ?)";
                })
                ->join(", ");

            $values = $itemsToCreate
                ->flatMap(function (array $item) {
                    return [$item["server_id"], $item["service_id"]];
                })
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
                ->map(function () {
                    return "(`server_id` = ? AND `service_id` = ?)";
                })
                ->join(" OR ");

            $values = $itemsToDelete
                ->flatMap(function (array $item) {
                    return [$item["server_id"], $item["service_id"]];
                })
                ->all();

            $this->db
                ->statement("DELETE FROM `ss_servers_services` WHERE {$keys}")
                ->execute($values);
        }
    }
}
