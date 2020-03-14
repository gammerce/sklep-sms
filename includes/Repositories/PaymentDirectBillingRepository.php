<?php
namespace App\Repositories;

use App\Models\PaymentDirectBilling;
use App\Support\Database;

class PaymentDirectBillingRepository
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
            $statement = $this->db->statement(
                "SELECT * FROM `ss_payment_direct_billing` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function findByExternalId($externalId)
    {
        if ($externalId) {
            $statement = $this->db->statement(
                "SELECT * FROM `ss_payment_direct_billing` WHERE `external_id` = ?"
            );
            $statement->execute([$externalId]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function create($externalId, $income, $cost, $ip, $platform, $free)
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_payment_direct_billing` " .
                    "SET `external_id` = ?, `income` = ?, `cost` = ?, `ip` = ?, `platform` = ?, `free` = ?"
            )
            ->execute([$externalId, $income, $cost, $ip, $platform, $free ? 1 : 0]);

        return $this->get($this->db->lastId());
    }

    private function mapToModel(array $data)
    {
        return new PaymentDirectBilling(
            (int) $data["id"],
            (string) $data["external_id"],
            (int) $data["income"],
            (int) $data["cost"],
            (string) $data["ip"],
            (string) $data["platform"],
            (bool) $data["free"]
        );
    }
}
