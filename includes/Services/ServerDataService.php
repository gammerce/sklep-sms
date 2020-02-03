<?php
namespace App\Services;

use App\Models\Price;
use App\Models\Server;
use App\Models\ServerService;
use App\Models\Service;
use App\Repositories\PriceRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
use App\ServiceModules\ExtraFlags\PlayerFlag;
use App\ServiceModules\ExtraFlags\PlayerFlagRepository;
use App\Support\Database;

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

    /** @var PlayerFlagRepository */
    private $playerFlagRepository;

    public function __construct(
        Database $db,
        PriceRepository $priceRepository,
        ServiceRepository $serviceRepository,
        ServerServiceRepository $serverServiceRepository,
        PlayerFlagRepository $playerFlagRepository
    ) {
        $this->db = $db;
        $this->priceRepository = $priceRepository;
        $this->serverServiceRepository = $serverServiceRepository;
        $this->serviceRepository = $serviceRepository;
        $this->playerFlagRepository = $playerFlagRepository;
    }

    /**
     * @param string[] $serviceIds
     * @param Server $server
     * @return Price[]
     */
    public function getPrices(array $serviceIds, Server $server)
    {
        if (!$serviceIds) {
            return [];
        }

        $keys = implode(",", array_fill(0, count($serviceIds), "?"));

        $statement = $this->db->statement(
            "SELECT * FROM `ss_prices` " .
                "WHERE (`server` = ? OR `server` IS NULL) AND `sms_price` IS NOT NULL AND `service` IN ({$keys}) " .
                "ORDER BY `service` ASC, `quantity` ASC"
        );
        $statement->execute(array_merge([$server->getId()], $serviceIds));

        return collect($statement)
            ->map(function (array $row) {
                return $this->priceRepository->mapToModel($row);
            })
            ->all();
    }

    /**
     * @param int $serverId
     * @return Service[]
     */
    public function getServices($serverId)
    {
        $serviceIds = collect($this->serverServiceRepository->findByServer($serverId))
            ->map(function (ServerService $serverService) {
                return $serverService->getServiceId();
            })
            ->all();

        return $this->serviceRepository->findMany($serviceIds);
    }

    /**
     * @param int $serverId
     * @return array
     */
    public function getPlayersFlags($serverId)
    {
        // ORDER BY is very important for binary search in plugins code
        $statement = $this->db->statement(
            <<<EOF
SELECT f.type, f.auth_data, f.password, 
(f.a > UNIX_TIMESTAMP() OR f.a = '-1') AS `a`, 
(f.b > UNIX_TIMESTAMP() OR f.b = '-1') AS `b`, 
(f.c > UNIX_TIMESTAMP() OR f.c = '-1') AS `c`, 
(f.d > UNIX_TIMESTAMP() OR f.d = '-1') AS `d`, 
(f.e > UNIX_TIMESTAMP() OR f.e = '-1') AS `e`, 
(f.f > UNIX_TIMESTAMP() OR f.f = '-1') AS `f`, 
(f.g > UNIX_TIMESTAMP() OR f.g = '-1') AS `g`, 
(f.h > UNIX_TIMESTAMP() OR f.h = '-1') AS `h`, 
(f.i > UNIX_TIMESTAMP() OR f.i = '-1') AS `i`,
(f.j > UNIX_TIMESTAMP() OR f.j = '-1') AS `j`,
(f.k > UNIX_TIMESTAMP() OR f.k = '-1') AS `k`,
(f.l > UNIX_TIMESTAMP() OR f.l = '-1') AS `l`,
(f.m > UNIX_TIMESTAMP() OR f.m = '-1') AS `m`,
(f.n > UNIX_TIMESTAMP() OR f.n = '-1') AS `n`,
(f.o > UNIX_TIMESTAMP() OR f.o = '-1') AS `o`,
(f.p > UNIX_TIMESTAMP() OR f.p = '-1') AS `p`,
(f.q > UNIX_TIMESTAMP() OR f.q = '-1') AS `q`,
(f.r > UNIX_TIMESTAMP() OR f.r = '-1') AS `r`,
(f.s > UNIX_TIMESTAMP() OR f.s = '-1') AS `s`,
(f.t > UNIX_TIMESTAMP() OR f.t = '-1') AS `t`,
(f.u > UNIX_TIMESTAMP() OR f.u = '-1') AS `u`,
(f.y > UNIX_TIMESTAMP() OR f.y = '-1') AS `y`,
(f.v > UNIX_TIMESTAMP() OR f.v = '-1') AS `v`,
(f.w > UNIX_TIMESTAMP() OR f.w = '-1') AS `w`,
(f.x > UNIX_TIMESTAMP() OR f.x = '-1') AS `x`,
(f.z > UNIX_TIMESTAMP() OR f.z = '-1') AS `z`
FROM `ss_players_flags` AS f
INNER JOIN `ss_servers` AS s ON s.id = f.server
WHERE s.id = ?
ORDER BY f.auth_data, f.type DESC
EOF
        );
        $statement->execute([$serverId]);

        return collect($statement)
            ->map(function (array $data) {
                $flags = collect($data)
                    ->filter(function ($value, $key) {
                        return in_array($key, PlayerFlag::FLAGS, true);
                    })
                    ->filter(function ($value) {
                        return !!$value;
                    })
                    ->keys()
                    ->join();

                return [
                    'type' => $data['type'],
                    'auth_data' => $data['auth_data'],
                    'password' => $data['password'],
                    'flags' => $flags,
                ];
            })
            ->all();
    }
}
