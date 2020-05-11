<?php
namespace App\Repositories;

use App\Models\PromoCode;
use App\PromoCode\QuantityType;
use App\Support\Database;
use DateTime;

// TODO Add expiration time
// TODO Add max usage limit
// TODO Remove service code payments
// TODO Migrate service code payments
// TODO Add used promo code along with bought service
// TODO Add method to update usage count

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
        $usageLimit,
        DateTime $expiresAt = null,
        $serviceId = null,
        $serverId = null,
        $userId = null
    ) {
        $this->db
            ->statement(
                <<<EOF
INSERT INTO `ss_promo_codes` 
SET
`code` = ?,
`quantity_type` = ?,
`quantity` = ?,
`usage_limit` = ?,
`expires_at` = ?,
`service_id` = ?,
`server` = ?,
`uid` = ?
EOF
            )
            ->execute([
                $code,
                $quantityType,
                $quantity,
                $usageLimit,
                $expiresAt,
                $serviceId,
                $serverId,
                $userId,
            ]);

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
            as_int($data["id"]),
            as_string($data["code"]),
            new QuantityType($data["quantity_type"]),
            as_int($data["quantity"]),
            as_datetime($data["created_at"]),
            as_int($data["usage_count"]),
            as_int($data["usage_limit"]),
            as_datetime($data["expires_at"]),
            as_string($data["service_id"]),
            as_int($data["server_id"]),
            as_int($data["user_id"])
        );
    }
}
