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
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT t.income, t.timestamp, t.server " .
                "FROM ({$this->settings['transactions_query']}) as t " .
                "WHERE t.free = '0' AND IFNULL(t.income,'') != '' AND t.payment != 'wallet' AND t.timestamp LIKE '%s-%s-%%' " .
                "ORDER BY t.timestamp ASC",
                [$year, $month]
            )
        );

        // Let's sum income by date (day precision) and server
        $data = [];
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $date = explode(" ", $row['timestamp'])[0];
            $serverId = $this->heart->getServer($row['server']) ? $row['server'] : 0;
            $data[$date][$serverId] += $row['income'];
        }

        return $data;
    }
}