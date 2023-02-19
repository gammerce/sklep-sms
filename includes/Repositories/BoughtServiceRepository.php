<?php
namespace App\Repositories;

use App\Models\BoughtService;
use App\Support\Database;

class BoughtServiceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get($id): ?BoughtService
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_bought_services` WHERE `id` = ?");
            $statement->bindAndExecute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function getByPaymentId(string $paymentId): ?BoughtService
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_bought_services` WHERE `payment_id` = ?"
        );
        $statement->bindAndExecute([$paymentId]);

        if ($data = $statement->fetch()) {
            return $this->mapToModel($data);
        }

        return null;
    }

    public function create(
        $userId,
        $method,
        $paymentId,
        $invoiceId,
        $serviceId,
        $serverId,
        $quantity,
        $authData,
        $email,
        $promoCode,
        $extraData = []
    ): BoughtService {
        $this->db
            ->statement(
                <<<EOF
INSERT INTO `ss_bought_services` 
SET
    `user_id` = ?,
    `payment` = ?,
    `payment_id` = ?,
    `invoice_id` = ?,
    `service_id` = ?,
    `server_id` = ?,
    `amount` = ?,
    `auth_data` = ?,
    `email` = ?,
    `promo_code` = ?,
    `extra_data` = ?
EOF
            )
            ->bindAndExecute([
                $userId ?: 0,
                $method,
                $paymentId,
                $invoiceId,
                $serviceId,
                $serverId ?: 0,
                $quantity,
                $authData ?: "",
                $email ?: "",
                $promoCode,
                json_encode($extraData),
            ]);

        return $this->get($this->db->lastId());
    }

    public function update(int $boughtServiceId, string $invoiceId): bool
    {
        $statement = $this->db->statement(
            "UPDATE `ss_bought_services` SET `invoice_id` = ? WHERE `id` = ?"
        );
        $statement->bindAndExecute([$invoiceId, $boughtServiceId]);
        return !!$statement->rowCount();
    }

    private function mapToModel(array $data): BoughtService
    {
        return new BoughtService(
            as_int($data["id"]),
            as_int($data["user_id"]),
            $data["payment"],
            as_string($data["payment_id"]),
            as_string($data["invoice_id"]),
            as_string($data["service_id"]),
            as_int($data["server_id"]),
            $data["amount"],
            as_string($data["auth_data"]),
            as_string($data["email"]),
            as_string($data["promo_code"]),
            json_decode($data["extra_data"], true)
        );
    }
}
