<?php
namespace App\Http\Controllers\Api\Server;

use App\Http\Responses\AssocResponse;
use App\Http\Responses\ServerJsonResponse;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Support\Database;
use App\System\ServerAuth;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

class UserServiceCollection
{
    public function get(Request $request, Database $db, ServerAuth $serverAuth)
    {
        $acceptHeader = AcceptHeader::fromString($request->headers->get("Accept"));
        $nick = $request->query->get("nick");
        $ip = $request->query->get("ip");
        $steamId = $request->query->get("steam_id");
        $server = $serverAuth->server();
        $serverId = $server ? $server->getId() : $request->query->get("server_id");

        $statement = $db->statement(
            <<<EOF
SELECT s.name AS `service`, us.expire
FROM `ss_user_service` AS us 
INNER JOIN `ss_user_service_extra_flags` AS usef ON usef.us_id = us.id 
INNER JOIN `ss_services` AS s ON us.service_id = s.id 
WHERE usef.server_id = ?
AND (us.expire > UNIX_TIMESTAMP() OR us.expire = -1)
AND (
    (usef.type = ? AND usef.auth_data = ?) 
    OR (usef.type = ? AND usef.auth_data = ?) 
    OR (usef.type = ? AND usef.auth_data = ?)
)
ORDER BY us.id DESC
EOF
        );
        $statement->bindAndExecute([
            $serverId,
            ExtraFlagType::TYPE_NICK,
            $nick,
            ExtraFlagType::TYPE_IP,
            $ip,
            ExtraFlagType::TYPE_SID,
            $steamId,
        ]);

        $data = collect($statement)
            ->map(
                fn(array $item) => [
                    "s" => $item["service"],
                    "e" => as_expiration_datetime_string($item["expire"]),
                ]
            )
            ->all();

        return $acceptHeader->has("application/json")
            ? new ServerJsonResponse($data)
            : new AssocResponse($data);
    }
}
