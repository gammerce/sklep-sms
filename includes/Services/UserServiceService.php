<?php
namespace App\Services;

use App\System\Database;
use App\System\Heart;

class UserServiceService
{
    /** @var Heart */
    private $heart;

    /** @var Database */
    private $db;

    public function __construct(Heart $heart, Database $db)
    {
        $this->heart = $heart;
        $this->db = $db;
    }

    /**
     * Pozyskuje z bazy wszystkie usługi użytkowników
     *
     * @param string|int $conditions Jezeli jest tylko jeden element w tablicy, to zwroci ten element zamiast tablicy
     * @param bool $takeOut
     *
     * @return array
     */
    public function find($conditions = '', $takeOut = true)
    {
        if (my_is_integer($conditions)) {
            $conditions = "WHERE `id` = " . intval($conditions);
        }

        $output = $usedTable = [];
        // Niestety dla każdego modułu musimy wykonać osobne zapytanie :-(
        foreach ($this->heart->getServicesModules() as $serviceModuleData) {
            $table = $serviceModuleData['class']::USER_SERVICE_TABLE;
            if (!strlen($table) || array_key_exists($table, $usedTable)) {
                continue;
            }

            $result = $this->db->query(
                "SELECT us.*, m.*, UNIX_TIMESTAMP() AS `now` FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $table .
                    "` AS m ON m.us_id = us.id " .
                    $conditions .
                    " ORDER BY us.id DESC "
            );

            foreach ($result as $row) {
                unset($row['us_id']);
                $output[$row['id']] = $row;
            }

            $usedTable[$table] = true;
        }

        ksort($output);
        $output = array_reverse($output);

        return $takeOut && count($output) == 1 ? $output[0] : $output;
    }
}
