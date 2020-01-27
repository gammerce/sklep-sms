<?php
namespace App\Repositories;

use App\Models\PaymentTransfer;
use App\Support\Database;

class PaymentTransferRepository
{
    /** @var Database */
    private $db;

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

    public function create($id, $income, $transferService, $ip, $platform, $free)
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_payment_transfer` " .
                    "SET `id` = ?, `income` = ?, `transfer_service` = ?, `ip` = ?, `platform` = ?, `free` = ? "
            )
            ->execute([$id, $income, $transferService, $ip, $platform, $free ? 1 : 0]);

        return $this->get($id);
    }

    private function mapToModel(array $data)
    {
        return new PaymentTransfer(
            $data["id"],
            (int) $data["income"],
            $data["transfer_service"],
            $data["ip"],
            $data["platform"],
            (bool) $data["free"]
        );
    }
}
