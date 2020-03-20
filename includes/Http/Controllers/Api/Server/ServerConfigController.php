<?php
namespace App\Http\Controllers\Api\Server;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidConfigException;
use App\Http\Responses\AssocResponse;
use App\Http\Responses\JsonResponse;
use App\Models\Price;
use App\Models\Server;
use App\Models\Service;
use App\Models\SmsNumber;
use App\Models\User;
use App\Repositories\ServerRepository;
use App\Repositories\UserRepository;
use App\Services\ServerDataService;
use App\System\Heart;
use App\System\ServerAuth;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// TODO Remove authorization by ip:port

class ServerConfigController
{
    public function get(
        Request $request,
        UserRepository $userRepository,
        ServerRepository $serverRepository,
        ServerDataService $serverDataService,
        Heart $heart,
        Settings $settings,
        ServerAuth $serverAuth
    ) {
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));
        $ip = $request->query->get("ip");
        $port = $request->query->get("port");
        $version = $request->query->get("version");
        $withPlayerFlags = $request->query->get("player_flags") === "1";
        $platform = $request->headers->get('User-Agent');

        $server = $serverAuth->server() ?: $serverRepository->findByIpPort($ip, $port);
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
                "Payment platform does not support sms payments [$smsPlatformId]."
            );
        }

        $smsNumbers = $smsModule::getSmsNumbers();
        $services = $serverDataService->getServices($server->getId());
        $serviceIds = collect($services)
            ->map(function (Service $service) {
                return $service->getId();
            })
            ->all();
        $prices = $serverDataService->getPrices($serviceIds, $server);

        $serviceItems = collect($services)->map(function (Service $service) {
            return [
                'i' => $service->getId(),
                'n' => $service->getName(),
                'd' => $service->getShortDescription(),
                'ta' => $service->getTag(),
                'f' => $service->getFlags(),
                'ty' => $service->getTypes(),
            ];
        });

        $priceItems = collect($prices)->map(function (Price $price) {
            return [
                'i' => $price->getId(),
                's' => $price->getServiceId(),
                'p' => $price->getSmsPrice(),
                // Replace null with -1 cause it's easier to handle it by plugins
                'q' => $price->getQuantity() !== null ? $price->getQuantity() : -1,
            ];
        });

        if ($withPlayerFlags) {
            $playersFlags = $serverDataService->getPlayersFlags($server->getId());
            $playerFlagItems = collect($playersFlags)->map(function (array $item) {
                return [
                    't' => $item['type'],
                    'a' => $item['auth_data'],
                    'p' => $item['password'],
                    'f' => $item['flags'],
                ];
            });
        }

        $smsNumberItems = collect($smsNumbers)->map(function (SmsNumber $smsNumber) {
            return $smsNumber->getNumber();
        });

        $steamIds = collect($userRepository->allWithSteamId())
            ->map(function (User $user) {
                return $user->getSteamId();
            })
            ->join(";");

        $serverRepository->touch($server->getId(), $platform, $version);

        $data = [
            'id' => $server->getId(),
            'license_token' => $settings->getLicenseToken(),
            'sms_platform_id' => $smsPlatformId,
            'sms_text' => $smsModule->getSmsCode(),
            'steam_ids' => "$steamIds;",
            'currency' => $settings->getCurrency(),
            'contact' => $settings->getContact(),
            'vat' => $settings->getVat(),
            'sn' => $smsNumberItems->all(),
            'se' => $serviceItems->all(),
            'pr' => $priceItems->all(),
        ];

        if (isset($playerFlagItems)) {
            $data['pf'] = $playerFlagItems->all();
        }

        return $acceptHeader->has("application/json")
            ? new JsonResponse($data)
            : new AssocResponse($data);
    }

    private function isVersionAcceptable($platform, $version)
    {
        $minimumVersions = [
            Server::TYPE_AMXMODX => "3.9.0",
            Server::TYPE_SOURCEMOD => "3.8.0",
        ];

        $minimumVersion = array_get($minimumVersions, $platform);

        return $minimumVersion && version_compare($version, $minimumVersion) >= 0;
    }
}
