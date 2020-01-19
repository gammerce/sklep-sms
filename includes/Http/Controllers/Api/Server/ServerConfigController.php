<?php
namespace App\Http\Controllers\Api\Server;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidConfigException;
use App\Http\Responses\ServerResponse;
use App\Models\Server;
use App\Models\ServerService;
use App\Models\User;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\UserRepository;
use App\System\Heart;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));
        $ip = $request->query->get("ip");
        $port = $request->query->get("port");
        $platform = $request->headers->get('User-Agent');
        $version = $request->query->get("version");

        $server = $serverRepository->findByIpPort($ip, $port);
        if (!$server) {
            throw new EntityNotFoundException();
        }

        if (!$this->isVersionAcceptable($platform, $version)) {
            return new Response('', 402);
        }

        $smsPlatformId = $server->getSmsPlatformId() ?: $settings->getSmsPlatformId();
        $smsModule = $heart->getPaymentModuleByPlatformId($smsPlatformId);

        if (!($smsModule instanceof SupportSms)) {
            throw new InvalidConfigException(
                "Payment module does not support sms [{$smsModule->getModuleId()}]."
            );
        }

        $serverServices = $serverServiceRepository->findByServer($server->getId());
        $serviceIds = array_map(function (ServerService $serverService) {
            return $serverService->getServiceId();
        }, $serverServices);

        $steamIds = array_map(function (User $user) {
            return $user->getSteamId();
        }, $userRepository->allWithSteamId());

        $serverRepository->touch($server->getId(), $platform, $version);

        $data = [
            'id' => $server->getId(),
            'sms_platform_id' => $smsPlatformId,
            'sms_module_id' => $smsModule->getModuleId(),
            'sms_text' => $smsModule->getSmsCode(),
            'services' => " " . implode(" ", $serviceIds) . " ",
            'steam_ids' => implode(";", $steamIds) . ";",
            'currency' => $settings->getCurrency(),
            'contact' => $settings->getContact(),
            'vat' => $settings->getVat(),
            'license_token' => $settings->getLicenseToken(),
        ];

        return $acceptHeader->has("application/json")
            ? new JsonResponse($data)
            : new ServerResponse($data);
    }

    private function isVersionAcceptable($platform, $version)
    {
        $minimumVersions = [
            Server::TYPE_AMXMODX => "3.8.0",
            Server::TYPE_SOURCEMOD => "3.7.0",
        ];

        $minimumVersion = array_get($minimumVersions, $platform);

        return $minimumVersion &&
            semantic_to_number($minimumVersion) <= semantic_to_number($version);
    }
}
