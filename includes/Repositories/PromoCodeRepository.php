<?php
namespace App\Repositories;

use App\Models\PromoCode;
use App\PromoCode\QuantityType;
use App\Support\Database;
use DateTime;

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
`server_id` = ?,
`user_id` = ?
EOF
            )
            ->execute([
                $code,
                $quantityType,
                $quantity,
                $usageLimit,
                serialize_date($expiresAt),
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

    public function useIt($id)
    {
        $this->db
            ->statement(
                "UPDATE `ss_promo_codes` SET `usage_count` = `usage_count` + 1 WHERE `id` = ?"
            )
            ->execute([$id]);
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
