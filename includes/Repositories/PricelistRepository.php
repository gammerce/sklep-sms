<?php
namespace App\Repositories;

use App\Database;
use App\Models\Pricelist;

class PricelistRepository
{
    /** @var Database */
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($service, $tariff, $amount, $server)
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "pricelist` (`service`, `tariff`, `amount`, `server`) " .
                    "VALUES( '%s', '%d', '%d', '%d' )",
                [$service, $tariff, $amount, $server]
            )
        );

        $id = $this->db->last_id();

        return new Pricelist($id, $service, $tariff, $amount, $server);
    }
}
