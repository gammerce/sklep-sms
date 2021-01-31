<?php
namespace App\Payment\Transfer;

use App\Payment\Interfaces\IServiceTakeOver;
use App\Repositories\TransactionRepository;
use App\Support\Database;

class TransferServiceTakeOver implements IServiceTakeOver
{
    private Database $db;
    private TransactionRepository $transactionRepository;

    public function __construct(Database $db, TransactionRepository $transactionRepository)
    {
        $this->db = $db;
        $this->transactionRepository = $transactionRepository;
    }

    public function isValid($paymentId, $serviceId, $authData, $serverId): bool
    {
        $statement = $this->db->statement(
            "SELECT * FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE t.payment = 'transfer' AND t.payment_id = ? AND `service_id` = ? AND `server_id` = ? AND `auth_data` = ?"
        );
        $statement->execute([$paymentId, $serviceId, $serverId, $authData]);

        return !!$statement->rowCount();
    }
}
