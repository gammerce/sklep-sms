<?php
namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\Price;
use App\Models\Server;
use App\Models\Service;
use App\Support\Database;

class PriceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_prices` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function getOrFail($id)
    {
        if ($paymentPlatform = $this->get($id)) {
            return $paymentPlatform;
        }

        throw new EntityNotFoundException();
    }

    public function create(
        $serviceId,
        $serverId,
        $smsPrice,
        $transferPrice,
        $directBillingPrice,
        $quantity,
        $discount
    ) {
        $this->db
            ->statement(
                "INSERT INTO `ss_prices` (`service_id`, `server_id`, `sms_price`, `transfer_price`, `direct_billing_price`, `quantity`, `discount`) " .
                    "VALUES ( ?, ?, ?, ?, ?, ?, ? )"
            )
            ->execute([
                $serviceId,
                $serverId,
                $smsPrice,
                $transferPrice,
                $directBillingPrice,
                $quantity,
                $discount,
            ]);

        return $this->get($this->db->lastId());
    }

    /**
     * @param Service $service
     * @param Server|null $server
     * @return Price[]
     */
    public function findByServiceServer(Service $service, Server $server = null)
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_prices` " .
                "WHERE `service_id` = ? AND (`server_id` = ? OR `server_id` IS NULL) " .
                "ORDER BY `quantity` ASC"
        );
        $statement->execute([$service->getId(), $server ? $server->getId() : null]);

        $prices = [];
        foreach ($statement as $row) {
            $prices[] = $this->mapToModel($row);
        }

        return $prices;
    }

    public function update(
        $id,
        $serviceId,
        $serverId,
        $smsPrice,
        $transferPrice,
        $directBillingPrice,
        $quantity,
        $discount
    ) {
        $statement = $this->db->statement(
            <<<EOF
            UPDATE `ss_prices` 
            SET
            `service_id` = ?,
            `server_id` = ?,
            `sms_price` = ?,
            `transfer_price` = ?,
            `direct_billing_price` = ?,
            `quantity` = ?,
            `discount` = ?
            WHERE `id` = ?
EOF
        );
        $statement->execute([
            $serviceId,
            $serverId,
            $smsPrice,
            $transferPrice,
            $directBillingPrice,
            $quantity,
            $discount,
            $id,
        ]);

        return !!$statement->rowCount();
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_prices` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function mapToModel(array $data)
    {
        return new Price(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["server_id"]),
            as_money($data["sms_price"]),
            as_money($data["transfer_price"]),
            as_money($data["direct_billing_price"]),
            as_int($data["quantity"]),
            as_int($data["discount"])
        );
    }
}
