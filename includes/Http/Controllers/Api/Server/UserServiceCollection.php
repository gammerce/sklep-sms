<?php
namespace App\Http\Controllers\Api\Server;

use App\Http\Responses\AssocResponse;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Validator;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Support\Database;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserServiceCollection
{
    public function get(Request $request, Database $db)
    {
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));

        $validator = new Validator(
            array_merge($request->query->all(), [
                'server_id' => as_int($request->query->get("server_id")),
            ]),
            [
                'server_id' => [new RequiredRule(), new ServerExistsRule()],
                'nick' => [],
                'ip' => [],
                'steam_id' => [],
            ]
        );

        $validated = $validator->validateOrFail();

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
            $validated['server_id'],
            ExtraFlagType::TYPE_NICK,
            $validated['nick'],
            ExtraFlagType::TYPE_IP,
            $validated['ip'],
            ExtraFlagType::TYPE_SID,
            $validated['steam_id'],
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
