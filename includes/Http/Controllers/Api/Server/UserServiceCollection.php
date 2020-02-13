<?php
namespace App\Http\Controllers\Api\Server;

use App\Http\Responses\AssocResponse;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Support\Database;
use App\System\ServerAuth;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserServiceCollection
{
    public function get(Request $request, Database $db, ServerAuth $serverAuth)
    {
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));
        $nick = $request->query->get("nick");
        $ip = $request->query->get("ip");
        $steamId = $request->query->get("steam_id");
        $serverId = $serverAuth->check() ? $serverAuth->server() : $request->query->get("server_id");

        $statement = $db->statement(
            <<<EOF
SELECT s.name AS `service`, us.expire
FROM `ss_user_service` AS us 
INNER JOIN `ss_user_service_extra_flags` AS usef ON usef.us_id = us.id 
INNER JOIN `ss_services` AS s ON us.service = s.id 
WHERE usef.server = ?
AND us.expire > UNIX_TIMESTAMP()
AND (
    (usef.type = ? AND usef.auth_data = ?) 
    OR (usef.type = ? AND usef.auth_data = ?) 
    OR (usef.type = ? AND usef.auth_data = ?)
)
ORDER BY us.id DESC
EOF
        );
        $statement->execute([
            $serverId,
            ExtraFlagType::TYPE_NICK,
            $nick,
            ExtraFlagType::TYPE_IP,
            $ip,
            ExtraFlagType::TYPE_SID,
            $steamId,
        ]);

        $data = collect($statement)
            ->map(function (array $item) {
                return [
                    "s" => $item["service"],
                    "e" => convert_expire($item["expire"]),
                ];
            })
            ->all();

        return $acceptHeader->has("application/json")
            ? new JsonResponse($data)
            : new AssocResponse($data);
    }
}
