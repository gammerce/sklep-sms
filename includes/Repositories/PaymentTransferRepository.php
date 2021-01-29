<?php
namespace App\Repositories;

use App\Models\PaymentTransfer;
use App\Support\Database;
use App\Support\Money;

class PaymentTransferRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_payment_transfer` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function create($id, $income, $cost, $transferService, $ip, $platform, $free)
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_payment_transfer` " .
                    "SET `id` = ?, `income` = ?, `cost` = ?, `transfer_service` = ?, `ip` = ?, `platform` = ?, `free` = ? "
            )
            ->execute([$id, $income, $cost, $transferService, $ip, $platform, $free ? 1 : 0]);

        return $this->get($id);
    }

    private function mapToModel(array $data)
    {
        return new PaymentTransfer(
            $data["id"],
            new Money($data["income"]),
            new Money($data["cost"]),
            $data["transfer_service"],
            $data["ip"],
            $data["platform"],
            (bool) $data["free"]
        );
    }
}
