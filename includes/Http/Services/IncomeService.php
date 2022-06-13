<?php
namespace App\Http\Services;

use App\Managers\ServerManager;
use App\Repositories\TransactionRepository;
use App\Support\Database;

class IncomeService
{
    private Database $db;
    private ServerManager $serverManager;
    private TransactionRepository $transactionRepository;

    public function __construct(
        Database $db,
        ServerManager $serverManager,
        TransactionRepository $transactionRepository
    ) {
        $this->db = $db;
        $this->serverManager = $serverManager;
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
        $statement->bindAndExecute(["$year-$month-%"]);

        // Let's sum income by date (day precision) and server
        $data = [];
        foreach ($statement as $row) {
            $transaction = $this->transactionRepository->mapToModel($row);
            $date = explode(" ", $transaction->getTimestamp())[0];
            $server = $this->serverManager->get($transaction->getServerId());
            $serverId = $server ? $server->getId() : 0;

            if (!isset($data[$date])) {
                $data[$date] = [];
            }

            if (!isset($data[$date][$serverId])) {
                $data[$date][$serverId] = 0;
            }

            // Income is never null because we ensure it in SQL query above
            $data[$date][$serverId] += $transaction->getIncome()->asInt();
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
            ->fetchColumn() ?:
            0;
    }
}
