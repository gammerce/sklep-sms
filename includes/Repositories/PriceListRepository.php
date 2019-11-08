<?php
namespace App\Repositories;

use App\System\Database;
use App\Models\PriceList;

class PriceListRepository
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

        $id = $this->db->lastId();

        return new PriceList($id, $service, $tariff, $amount, $server);
    }
}
