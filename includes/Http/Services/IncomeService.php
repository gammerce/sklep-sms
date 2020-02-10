<?php
namespace App\Http\Services;

use App\Repositories\TransactionRepository;
use App\Support\Database;
use App\System\Heart;

class IncomeService
{
    /** @var Database */
    private $db;

    /** @var Heart */
    private $heart;

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(
        Database $db,
        Heart $heart,
        TransactionRepository $transactionRepository
    ) {
        $this->db = $db;
        $this->heart = $heart;
        $this->transactionRepository = $transactionRepository;
    }

    public function get($year, $month)
    {
        $statement = $this->db->statement(
            "SELECT * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE t.free = '0' AND IFNULL(t.income,'') != '' AND t.payment != 'wallet' AND t.timestamp LIKE ? " .
                "ORDER BY t.timestamp ASC"
        );
        $statement->execute(["$year-$month-%"]);

        // Let's sum income by date (day precision) and server
        $data = [];
        foreach ($statement as $row) {
            $transaction = $this->transactionRepository->mapToModel($row);
            $date = explode(" ", $transaction->getTimestamp())[0];
            $server = $this->heart->getServer($transaction->getServerId());
            $serverId = $server ? $server->getId() : 0;

            if (!isset($data[$date])) {
                $data[$date] = [];
            }

            if (!isset($data[$date][$serverId])) {
                $data[$date][$serverId] = 0;
            }

            $data[$date][$serverId] += $transaction->getIncome();
        }

        return $data;
    }

    public function getWholeIncome()
    {
        return $this->db
            ->query(
                "SELECT SUM(t.income) " .
                    "FROM ({$this->transactionRepository->getQuery()}) as t " .
                    "WHERE t.free = '0' AND t.payment != 'wallet' "
            )
            ->fetchColumn();
    }
}
