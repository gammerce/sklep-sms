<?php
namespace App\Repositories;

use App\Models\PaymentDirectBilling;
use App\Support\Database;
use App\Support\Money;

class PaymentDirectBillingRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get($id): ?PaymentDirectBilling
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT * FROM `ss_payment_direct_billing` WHERE `id` = ?"
            );
            $statement->bindAndExecute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function findByExternalId($externalId): ?PaymentDirectBilling
    {
        if ($externalId) {
            $statement = $this->db->statement(
                "SELECT * FROM `ss_payment_direct_billing` WHERE `external_id` = ?"
            );
            $statement->bindAndExecute([$externalId]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function create($externalId, $income, $cost, $ip, $platform, $free): PaymentDirectBilling
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_payment_direct_billing` " .
                    "SET `external_id` = ?, `income` = ?, `cost` = ?, `ip` = ?, `platform` = ?, `free` = ?"
            )
            ->bindAndExecute([$externalId, $income, $cost, $ip, $platform, $free ? 1 : 0]);

        return $this->get($this->db->lastId());
    }

    private function mapToModel(array $data): PaymentDirectBilling
    {
        return new PaymentDirectBilling(
            (int) $data["id"],
            (string) $data["external_id"],
            new Money($data["income"]),
            new Money($data["cost"]),
            (string) $data["ip"],
            (string) $data["platform"],
            (bool) $data["free"]
        );
    }
}
