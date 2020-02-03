<?php
namespace App\Repositories;

use App\Models\PaymentCode;
use App\Support\Database;

class PaymentCodeRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($code, $ip, $platform)
    {
        $this->db
            ->statement("INSERT INTO `ss_payment_code` SET `code` = ?, `ip` = ?, `platform` = ?")
            ->execute([$code, $ip, $platform]);

        return $this->get($this->db->lastId());
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_payment_code` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    private function mapToModel(array $data)
    {
        return new PaymentCode(as_int($data['id']), $data['code'], $data['ip'], $data['platform']);
    }
}
