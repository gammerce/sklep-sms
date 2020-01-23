<?php
namespace App\Repositories;

use App\System\Database;

class SettingsRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function update(array $values)
    {
        if (empty($values)) {
            return false;
        }

        $conditions = [];
        $keys = [];
        $params = [];

        foreach ($values as $key => $value) {
            $conditions[] = "WHEN ? THEN ?";
            $params[] = $key;
            $params[] = (string) $value;
        }

        foreach (array_keys($values) as $key) {
            $keys[] = '?';
            $params[] = $key;
        }

        $statement = $this->db->statement(
            "UPDATE `ss_settings` " .
                "SET `value` = CASE `key` " .
                implode(" ", $conditions) .
                " END " .
                "WHERE `key` IN ( " .
                implode(", ", $keys) .
                " )"
        );
        $statement->execute($params);

        return !!$statement->rowCount();
    }
}
