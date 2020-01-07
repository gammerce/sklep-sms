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

        foreach ($values as $key => $value) {
            $conditions[] = $this->db->prepare("WHEN '%s' THEN '%s'", [$key, $value]);
            $keys[] = $this->db->prepare("'%s'", [$key]);
        }

        $statement = $this->db->query(
            "UPDATE `" .
                TABLE_PREFIX .
                "settings` " .
                "SET `value` = CASE `key` " .
                implode(" ", $conditions) .
                " END " .
                "WHERE `key` IN ( " .
                implode(", ", $keys) .
                " )"
        );

        return !!$statement->rowCount();
    }
}
