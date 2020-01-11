<?php
namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\Price;
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
