<?php
namespace App\Http\Controllers\Api\Server;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Support\Database;
use Symfony\Component\HttpFoundation\Request;

class UserServiceCollection
{
    public function get(Request $request, Database $db)
    {
        // TODO Get it
        $serverId = "";
        $authData = "";

        $statement = $db->statement(
            <<<EOF
SELECT s.name AS `service`, FROM_UNIXTIME(us.expire) AS `expire`, us.expire AS `expire_num` FROM `%suser_service` AS us 
INNER JOIN `ss_user_service_extra_flags` AS usef ON usef.us_id = us.id 
INNER JOIN `ss_services` AS s ON us.service = s.id 
WHERE usef.server = ? 
AND (
    (usef.type = ? AND usef.auth_data = ?) 
    OR (usef.type = ? AND usef.auth_data = ?) 
    OR (usef.type = ? AND usef.auth_data = ?)
)
EOF
        );
        $statement->execute([$serverId, ExtraFlagType::TYPE_NICK, $authData, ExtraFlagType::TYPE_IP, $authData, ExtraFlagType::TYPE_SID, $authData]);

        // TODO Get it
        $ip = "";
        $port = "";
        $statement = $db->statement(
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
WHERE s.ip = ?
AND s.port = ?
ORDER BY f.auth_data, f.type DESC
EOF
        );
        $statement->execute([$ip, $port]);
    }
}
