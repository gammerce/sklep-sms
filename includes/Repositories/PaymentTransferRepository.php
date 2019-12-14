<?php
namespace App\Repositories;

use App\Models\PaymentTransfer;
use App\System\Database;

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
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "payment_transfer` WHERE `id` = '%s'",
                    [$id]
                )
            );

            if ($data = $this->db->fetchArrayAssoc($result)) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function create($id, $income, $transferService, $ip, $platform)
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_transfer` " .
                    "SET `id` = '%s', `income` = '%d', `transfer_service` = '%s', `ip` = '%s', `platform` = '%s' ",
                [$id, $income, $transferService, $ip, $platform]
            )
        );

        return $this->get($this->db->lastId());
    }

    private function mapToModel(array $data)
    {
        return new PaymentTransfer(
            $data["id"],
            $data["income"],
            $data["transfer_service"],
            $data["ip"],
            $data["platform"]
        );
    }
}
