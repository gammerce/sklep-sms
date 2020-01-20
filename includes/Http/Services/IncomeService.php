<?php
namespace App\Http\Services;

use App\System\Database;
use App\System\Heart;
use App\System\Settings;

class IncomeService
{
    /** @var Database */
    private $db;

    /** @var Settings */
    private $settings;

    /** @var Heart */
    private $heart;

    public function __construct(Database $db, Settings $settings, Heart $heart)
    {
        $this->db = $db;
        $this->settings = $settings;
        $this->heart = $heart;
    }

    public function get($year, $month)
    {
        $statement = $this->db->statement(
            "SELECT t.income, t.timestamp, t.server " .
                "FROM ({$this->settings['transactions_query']}) as t " .
                "WHERE t.free = '0' AND IFNULL(t.income,'') != '' AND t.payment != 'wallet' AND t.timestamp LIKE ? " .
                "ORDER BY t.timestamp ASC"
        );
        $statement->execute(
            ["$year-$month-%"]
        );

        // Let's sum income by date (day precision) and server
        $data = [];
        foreach ($statement as $row) {
            $date = explode(" ", $row['timestamp'])[0];
            $serverId = $this->heart->getServer($row['server']) ? $row['server'] : 0;

            if (!isset($data[$date])) {
                $data[$date] = [];
            }

            if (!isset($data[$date][$serverId])) {
                $data[$date][$serverId] = 0;
            }

            $data[$date][$serverId] += $row['income'];
        }

        return $data;
    }

    public function getWholeIncome()
    {
        return $this->db->query(
            "SELECT SUM(t.income) " .
            "FROM ({$this->settings['transactions_query']}) as t " .
            "WHERE t.free = '0' AND t.payment != 'wallet' "
        )->fetchColumn();
    }
}
