<?php
namespace App\Services;

use App\Models\Price;
use App\Models\Server;
use App\Models\ServerService;
use App\Models\Service;
use App\Repositories\PriceRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
use App\System\Database;

class ServerDataService
{
    /** @var Database */
    private $db;

    /** @var PriceRepository */
    private $priceRepository;

    /** @var ServerServiceRepository */
    private $serverServiceRepository;

    /** @var ServiceRepository */
    private $serviceRepository;

    public function __construct(
        Database $db,
        PriceRepository $priceRepository,
        ServiceRepository $serviceRepository,
        ServerServiceRepository $serverServiceRepository
    ) {
        $this->db = $db;
        $this->priceRepository = $priceRepository;
        $this->serverServiceRepository = $serverServiceRepository;
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * @param array $serviceIds
     * @param Server $server
     * @return Price[]
     */
    public function findPrices(array $serviceIds, Server $server)
    {
        $keys = implode(",", array_fill(0, count($serviceIds), "?"));

        $statement = $this->db->statement(
            "SELECT * FROM `" .
                TABLE_PREFIX .
                "prices` " .
                "WHERE (`server` = ? OR `server` IS NULL) AND `sms_price` IS NOT NULL AND `service` IN ({$keys}) " .
                "ORDER BY `service` ASC, `quantity` ASC"
        );
        $statement->execute(array_merge([$server->getId()], $serviceIds));

        return collect($statement)
            ->map(function (array $row) {
                return $this->priceRepository->mapToModel($row);
            })
            ->toArray();
    }

    /**
     * @param string $serverId
     * @return Service[]
     */
    public function findServices($serverId)
    {
        $serviceIds = collect($this->serverServiceRepository->findByServer($serverId))
            ->map(function (ServerService $serverService) {
                return $serverService->getServiceId();
            })
            ->toArray();

        return $this->serviceRepository->findMany($serviceIds);
    }
}
