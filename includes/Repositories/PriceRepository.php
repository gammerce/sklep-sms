<?php
namespace App\Repositories;

use App\Models\PriceList;
use App\System\Database;

class PriceRepository
{
    /** @var Database */
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT * FROM `" . TABLE_PREFIX . "pricelist` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function create($service, $tariff, $amount, $server)
    {
            $this->db->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "pricelist` (`service`, `tariff`, `amount`, `server`) " .
                    "VALUES ( ?, ?, ?, ? )"
            )->execute(
                [$service, $tariff, $amount, $server]
            );

        return $this->get($this->db->lastId());
    }

    private function mapToModel(array $data)
    {
        return new PriceList(
            (int) $data['id'],
            $data['service'],
            (int) $data['tariff'],
            (int) $data['amount'],
            (int) $data['server']
        );
    }
}
