<?php
namespace App\Repositories;

use App\Models\SmsCode;
use App\Support\Database;
use DateTime;

// TODO Fix displaying date
// TODO Store last minute of a day
// TODO Delete old sms codes
// TODO Do not accept too old sms codes

class SmsCodeRepository
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
            $statement = $this->db->statement("SELECT * FROM `ss_sms_codes` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function findByCodeAndPrice($code, $smsPrice)
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_sms_codes` WHERE `code` = ? AND `sms_price` = ?"
        );
        $statement->execute([$code, $smsPrice]);

        if ($data = $statement->fetch()) {
            return $this->mapToModel($data);
        }

        return null;
    }

    /**
     * @param string $code
     * @param int $smsPrice
     * @param bool $free
     * @param DateTime|null $expires
     * @return SmsCode
     */
    public function create($code, $smsPrice, $free, DateTime $expires = null)
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_sms_codes` SET `code` = ?, `sms_price` = ?, `free` = ?, `expires_at` = ?"
            )
            ->execute([$code, $smsPrice, $free ? 1 : 0, get_date_for_database($expires)]);

        return $this->get($this->db->lastId());
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_sms_codes` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function mapToModel(array $data)
    {
        return new SmsCode(
            (int) $data['id'],
            $data['code'],
            (int) $data['sms_price'],
            (bool) $data['free'],
            as_datetime($data['expires_at'])
        );
    }
}
