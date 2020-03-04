<?php
namespace App\Payment\Sms;

use App\Payment\Interfaces\IServiceTakeOver;
use App\Repositories\TransactionRepository;
use App\Support\Database;

class TransferServiceTakeOver implements IServiceTakeOver
{
    /** @var Database */
    private $db;

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(Database $db, TransactionRepository $transactionRepository)
    {
        $this->db = $db;
        $this->transactionRepository = $transactionRepository;
    }

    public function isValid($paymentId, $serviceId, $authData, $serverId)
    {
        $statement = $this->db->statement(
            "SELECT * FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE t.payment = 'transfer' AND t.payment_id = ? AND `service` = ? AND `server` = ? AND `auth_data` = ?"
        );
        $statement->execute([$paymentId, $serviceId, $serverId, $authData]);

        return !!$statement->rowCount();
    }
}
