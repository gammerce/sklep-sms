<?php
namespace App\Repositories;

use App\Models\PromoCode;
use App\PromoCode\QuantityType;
use App\Support\Database;

// TODO Add expiration time
// TODO Add max usage limit
// TODO Remove service code payments
// TODO Migrate service code payments
// TODO Add used promo code along with bought service

class PromoCodeRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(
        $code,
        QuantityType $quantityType,
        $quantity,
        $serviceId = null,
        $serverId = null,
        $uid = null
    ) {
        $this->db
            ->statement(
                "INSERT INTO `ss_promo_codes` " .
                    "SET `code` = ?, `quantity_type` = ?, `quantity` = ?, `service` = ?, `server` = ?, `uid` = ?"
            )
            ->execute([$code, $quantityType, $quantity, $serviceId, $serverId, $uid]);

        return $this->get($this->db->lastId());
    }

    /**
     * @param int $id
     * @return PromoCode|null
     */
    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_promo_codes` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * @param string $code
     * @return PromoCode|null
     */
    public function findByCode($code)
    {
        if (strlen($code)) {
            $statement = $this->db->statement("SELECT * FROM `ss_promo_codes` WHERE `code` = ?");
            $statement->execute([$code]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_promo_codes` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function mapToModel(array $data)
    {
        return new PromoCode(
            as_int($data['id']),
            $data['code'],
            new QuantityType($data['quantity_type']),
            $data['quantity'],
            $data['service'],
            as_int($data['server']),
            as_int($data['uid']),
            $data['timestamp']
        );
    }
}
