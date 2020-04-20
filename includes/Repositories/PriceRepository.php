<?php
namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\Price;
use App\Models\Server;
use App\Models\Service;
use App\Support\Database;

class PriceRepository
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
        $service,
        $server,
        $smsPrice,
        $transferPrice,
        $directBillingPrice,
        $quantity
    ) {
        $this->db
            ->statement(
                "INSERT INTO `ss_prices` (`service`, `server`, `sms_price`, `transfer_price`, `direct_billing_price`, `quantity`) " .
                    "VALUES ( ?, ?, ?, ?, ?, ? )"
            )
            ->execute([
                $service,
                $server,
                $smsPrice,
                $transferPrice,
                $directBillingPrice,
                $quantity,
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
                "WHERE `service` = ? AND (`server` = ? OR `server` IS NULL) " .
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
        $service,
        $server,
        $smsPrice,
        $transferPrice,
        $directBillingPrice,
        $quantity
    ) {
        $statement = $this->db->statement(
            "UPDATE `ss_prices` " .
                "SET `service` = ?, `server` = ?, `sms_price` = ?, `transfer_price` = ?, `direct_billing_price` = ?, `quantity` = ? " .
                "WHERE `id` = ?"
        );
        $statement->execute([
            $service,
            $server,
            $smsPrice,
            $transferPrice,
            $directBillingPrice,
            $quantity,
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
            as_int($data['id']),
            $data['service'],
            as_int($data['server']),
            as_int($data['sms_price']),
            as_int($data['transfer_price']),
            as_int($data['direct_billing_price']),
            as_int($data['quantity']),
            as_int($data['discount'])
        );
    }
}
