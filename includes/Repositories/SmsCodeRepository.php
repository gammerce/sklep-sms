<?php
namespace App\Repositories;

use App\Models\SmsCode;
use App\Support\Database;
use App\Support\Money;
use DateTime;

class SmsCodeRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $id
     * @return SmsCode|null
     */
    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_sms_codes` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * @param string $code
     * @param Money $smsPrice
     * @return SmsCode|null
     */
    public function findByCodeAndPrice($code, Money $smsPrice)
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_sms_codes` WHERE `code` = ? AND `sms_price` = ? AND (`expires_at` IS NULL OR `expires_at` > NOW())"
        );
        $statement->execute([$code, $smsPrice->asInt()]);

        if ($data = $statement->fetch()) {
            return $this->mapToModel($data);
        }

        return null;
    }

    /**
     * @param string $code
     * @param Money $smsPrice
     * @param bool $free
     * @param DateTime|null $expires
     * @return SmsCode
     */
    public function create($code, Money $smsPrice, $free, DateTime $expires = null)
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_sms_codes` SET `code` = ?, `sms_price` = ?, `free` = ?, `expires_at` = ?"
            )
            ->execute([$code, $smsPrice->asInt(), $free ? 1 : 0, serialize_date($expires)]);

        return $this->get($this->db->lastId());
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_sms_codes` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    /**
     * @param array $data
     * @return SmsCode
     */
    public function mapToModel(array $data)
    {
        return new SmsCode(
            (int) $data["id"],
            $data["code"],
            new Money($data["sms_price"]),
            (bool) $data["free"],
            as_datetime($data["expires_at"])
        );
    }
}
