<?php
namespace App\Http\Controllers\Api\Server;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ServerResponse;
use App\Models\Server;
use App\Models\ServerService;
use App\Models\User;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\UserRepository;
use App\System\Heart;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServerConfigController
{
    public function get(
        Request $request,
        UserRepository $userRepository,
        ServerRepository $serverRepository,
        ServerServiceRepository $serverServiceRepository,
        Heart $heart,
        Settings $settings
    ) {
        $ip = $request->query->get("ip");
        $port = $request->query->get("port");
        $type = $request->query->get("type");
        $version = $request->query->get("version");

        $server = $serverRepository->findByIpPort($ip, $port);
        if (!$server) {
            throw new EntityNotFoundException();
        }

        if (!$this->isVersionAcceptable($type, $version)) {
            return new Response('', 402);
        }

        $smsPlatformId = $server->getSmsPlatformId() ?: $settings->getSmsPlatformId();
        $smsModule = $heart->getPaymentModuleByPlatformIdOrFail($smsPlatformId);

        $serverServices = $serverServiceRepository->findByServer($server->getId());
        $serviceIds = array_map(function (ServerService $serverService) {
            return $serverService->getServiceId();
        }, $serverServices);

        $steamIds = array_map(function (User $user) {
            return $user->getSteamId();
        }, $userRepository->allWithSteamId());

        $serverRepository->touch($server->getId(), $type, $version);

        return new ServerResponse([
            'id' => $server->getId(),
            'name' => $server->getName(),
            'sms_platform_id' => $smsPlatformId,
            'sms_module_id' => $smsModule->getModuleId(),
            'services' => " " . implode(" ", $serviceIds) . " ",
            'steam_ids' => implode(";", $steamIds) . ";",
        ]);
    }

    private function isVersionAcceptable($type, $version)
    {
        $minimumVersions = [
            Server::TYPE_AMXMODX => "3.8.0",
            Server::TYPE_SOURCEMOD => "3.7.0",
        ];

        $minimumVersion = array_get($minimumVersions, $type);

        return $minimumVersion &&
            semantic_to_number($minimumVersion) <= semantic_to_number($version);
    }
}
