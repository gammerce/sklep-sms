<?php
namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\Price;
use App\Models\Server;
use App\Models\Service;
use App\System\Database;

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
            $statement = $this->db->statement(
                "SELECT * FROM `" . TABLE_PREFIX . "prices` WHERE `id` = ?"
            );
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

    public function create($service, $server, $smsPrice, $transferPrice, $quantity)
    {
        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "prices` (`service`, `server`, `sms_price`, `transfer_price`, `quantity`) " .
                    "VALUES ( ?, ?, ?, ?, ? )"
            )
            ->execute([$service, $server, $smsPrice, $transferPrice, $quantity]);

        return $this->get($this->db->lastId());
    }

    public function findByServiceServerAndSmsPrice(Service $service, Server $server, $smsPrice)
    {
        $statement = $this->db->statement(
            "SELECT * FROM `" .
                TABLE_PREFIX .
                "prices` " .
                "WHERE `service` = ? AND (`server` = ? OR `server` IS NULL) AND `sms_price` = ?"
        );
        $statement->execute([$service->getId(), $server->getId(), $smsPrice]);
        $data = $statement->fetch();

        return $data ? $this->mapToModel($data) : null;
    }

    /**
     * @param Service $service
     * @return Price[]
     */
    public function findByService(Service $service)
    {
        $statement = $this->db->statement(
            "SELECT * FROM `" .
                TABLE_PREFIX .
                "prices` " .
                "WHERE `service` = ? " .
                "ORDER BY `quantity` ASC"
        );
        $statement->execute([$service->getId()]);

        $prices = [];
        foreach ($statement as $row) {
            $prices[] = $this->mapToModel($row);
        }

        return $prices;
    }

    public function update($id, $service, $server, $smsPrice, $transferPrice, $quantity)
    {
        $statement = $this->db->statement(
            "UPDATE `" .
                TABLE_PREFIX .
                "prices` " .
                "SET `service` = ?, `server` = ?, `sms_price` = ?, `transfer_price` = ?, `quantity` = ? " .
                "WHERE `id` = ?"
        );
        $statement->execute([$service, $server, $smsPrice, $transferPrice, $quantity, $id]);

        return !!$statement->rowCount();
    }

    public function delete($id)
    {
        $statement = $this->db->statement(
            "DELETE FROM `" . TABLE_PREFIX . "prices` WHERE `id` = ?"
        );
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    private function mapToModel(array $data)
    {
        return new Price(
            (int) $data['id'],
            $data['service'],
            $data['server'] !== null ? (int) $data['server'] : null,
            $data['sms_price'] !== null ? (int) $data['sms_price'] : null,
            $data['transfer_price'] !== null ? (int) $data['transfer_price'] : null,
            (int) $data['quantity']
        );
    }
}
