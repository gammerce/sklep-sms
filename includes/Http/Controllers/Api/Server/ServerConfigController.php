<?php
namespace App\Http\Controllers\Api\Server;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidConfigException;
use App\Http\Responses\ServerResponse;
use App\Models\Price;
use App\Models\Server;
use App\Models\Service;
use App\Models\SmsNumber;
use App\Models\User;
use App\Repositories\ServerRepository;
use App\Repositories\UserRepository;
use App\Services\ServerDataService;
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
        ServerDataService $serverDataService,
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
                "Payment platform does not support sms payments [$smsPlatformId]."
            );
        }

        $smsNumbers = $smsModule::getSmsNumbers();
        $services = $serverDataService->findServices($server->getId());
        $serviceIds = collect($services)
            ->map(function (Service $service) {
                return $service->getId();
            })
            ->all();
        $prices = $serverDataService->findPrices($serviceIds, $server);

        $serviceItems = collect($services)
            ->map(function (Service $service) {
                return [
                    'i' => $service->getId(),
                    'n' => $service->getName(),
                    'd' => $service->getShortDescription(),
                    'ta' => $service->getTag(),
                    'f' => $service->getFlags(),
                    'ty' => $service->getTypes(),
                ];
            })
            ->all();

        $priceItems = collect($prices)
            ->map(function (Price $price) {
                return [
                    'i' => $price->getId(),
                    's' => $price->getServiceId(),
                    'p' => $price->getSmsPrice(),
                    // Replace null with -1 cause it's easier to handle it by plugins
                    'q' => $price->getQuantity() !== null ? $price->getQuantity() : -1,
                ];
            })
            ->all();

        $smsNumberItems = collect($smsNumbers)
            ->map(function (SmsNumber $smsNumber) {
                return $smsNumber->getNumber();
            })
            ->all();

        $steamIds = collect($userRepository->allWithSteamId())
            ->map(function (User $user) {
                return $user->getSteamId();
            })
            ->all();

        $serverRepository->touch($server->getId(), $platform, $version);

        $data = [
            'id' => $server->getId(),
            'license_token' => $settings->getLicenseToken(),
            'sms_platform_id' => $smsPlatformId,
            'sms_text' => $smsModule->getSmsCode(),
            'steam_ids' => implode(";", $steamIds) . ";",
            'currency' => $settings->getCurrency(),
            'contact' => $settings->getContact(),
            'vat' => $settings->getVat(),
            'sn' => $smsNumberItems,
            'se' => $serviceItems,
            'pr' => $priceItems,
        ];

        return $acceptHeader->has("application/json")
            ? new JsonResponse($data)
            : new ServerResponse($data);
    }

    private function isVersionAcceptable($platform, $version)
    {
        $minimumVersions = [
            Server::TYPE_AMXMODX => "3.9.0",
            Server::TYPE_SOURCEMOD => "3.8.0",
        ];

        $minimumVersion = array_get($minimumVersions, $platform);

        return $minimumVersion &&
            semantic_to_number($minimumVersion) <= semantic_to_number($version);
    }
}
