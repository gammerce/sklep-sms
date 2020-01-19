<?php
namespace App\Repositories;

use App\Models\SmsCode;
use App\System\Database;

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
            $statement = $this->db->statement(
                "SELECT * FROM `" . TABLE_PREFIX . "sms_codes` WHERE `id` = ?"
            );
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
            "SELECT * FROM `" . TABLE_PREFIX . "sms_codes` WHERE `code` = ? AND `sms_price` = ?"
        );
        $statement->execute([$code, $smsPrice]);

        if ($data = $statement->fetch()) {
            return $this->mapToModel($data);
        }

        return null;
    }

    public function create($code, $smsPrice, $free)
    {
        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "sms_codes` " .
                    "SET `code` = ?, `sms_price` = ?, `free` = ?"
            )
            ->execute([$code, $smsPrice, $free ? 1 : 0]);

        return $this->get($this->db->lastId());
    }

    public function delete($id)
    {
        $statement = $this->db->statement(
            "DELETE FROM `" . TABLE_PREFIX . "sms_codes` " . "WHERE `id` = ?"
        );
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function mapToModel(array $data)
    {
        return new SmsCode($data['id'], $data['code'], $data['sms_price'], $data['free']);
    }
}
