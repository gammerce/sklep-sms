<?php
namespace App\Http\Controllers\Api\Server;

use App\Http\Responses\AssocResponse;
use App\Http\Responses\JsonResponse;
use App\Services\ServerDataService;
use App\System\ServerAuth;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

class PlayerFlagCollection
{
    public function get(
        Request $request,
        ServerAuth $serverAuth,
        ServerDataService $serverDataService
    ) {
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));
        $server = $serverAuth->server();

        $playersFlags = $serverDataService->getPlayersFlags($server->getId());
        $playerFlagItems = collect($playersFlags)->map(function (array $item) {
            return [
                't' => $item['type'],
                'a' => $item['auth_data'],
                'p' => $item['password'],
                'f' => $item['flags'],
            ];
        });

        $data = [
            "pf" => $playerFlagItems->all(),
        ];

        return $acceptHeader->has("application/json")
            ? new JsonResponse($data)
            : new AssocResponse($data);
    }
}
