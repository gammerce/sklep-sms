<?php
namespace App\Http\Controllers\Api\Server;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidConfigException;
use App\Http\Responses\AssocResponse;
use App\Http\Responses\ServerJsonResponse;
use App\Managers\PaymentModuleManager;
use App\Models\Price;
use App\Models\Service;
use App\Models\SmsNumber;
use App\Models\User;
use App\Repositories\ServerRepository;
use App\Repositories\UserRepository;
use App\Server\Platform;
use App\Server\ServerDataService;
use App\Service\UserServiceAccessService;
use App\System\Auth;
use App\System\ExternalConfigProvider;
use App\System\ServerAuth;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServerConfigController
{
    public function get(
        Request $request,
        UserRepository $userRepository,
        ServerRepository $serverRepository,
        ServerDataService $serverDataService,
        PaymentModuleManager $paymentModuleManager,
        Settings $settings,
        ServerAuth $serverAuth,
        Auth $auth,
        UserServiceAccessService $userServiceAccessService,
        ExternalConfigProvider $externalConfigProvider
    ) {
        $acceptHeader = AcceptHeader::fromString($request->headers->get("Accept"));
        $version = $request->query->get("version");
        $platform = as_server_type(get_platform($request));

        $server = $serverAuth->server();
        if (!$server) {
            throw new EntityNotFoundException();
        }

        if (!$platform || !$this->isVersionAcceptable($version, $platform)) {
            return new Response("", Response::HTTP_PAYMENT_REQUIRED);
        }

        $smsPlatformId = $server->getSmsPlatformId() ?: $settings->getSmsPlatformId();
        $smsModule = $paymentModuleManager->getByPlatformId($smsPlatformId);

        if (!($smsModule instanceof SupportSms)) {
            throw new InvalidConfigException(
                "Payment platform does not support sms payments [$smsPlatformId]."
            );
        }

        $smsNumbers = $smsModule->getSmsNumbers();
        $services = collect($serverDataService->getServices($server->getId()))->filter(
            fn(Service $service) => $userServiceAccessService->canUserUseService(
                $service,
                $auth->user()
            )
        );
        $serviceIds = $services->map(fn(Service $service) => $service->getId())->all();
        $prices = $serverDataService->getPrices($serviceIds, $server);

        $serviceItems = $services->map(
            fn(Service $service) => [
                "i" => $service->getId(),
                "n" => $service->getNameI18n(),
                "d" => $service->getShortDescriptionI18n(),
                "ta" => $service->getTag(),
                "f" => $service->getFlags(),
                "ty" => $service->getTypes(),
            ]
        );

        $priceItems = collect($prices)->map(
            fn(Price $price) => [
                "i" => $price->getId(),
                "s" => $price->getServiceId(),
                "p" => as_int($price->getSmsPrice()),
                // Replace null with -1 cause it's easier to handle it by plugins
                "q" => $price->getQuantity() !== null ? $price->getQuantity() : -1,
                // Replace null with 0 cause it's easier to handle it by plugins
                "d" => $price->getDiscount() ?: 0,
            ]
        );

        $playersFlags = $serverDataService->getPlayersFlags($server->getId());
        $playerFlagItems = collect($playersFlags)->map(
            fn(array $item) => [
                "t" => $item["type"],
                "a" => $item["auth_data"],
                "p" => $item["password"],
                "f" => $item["flags"],
            ]
        );

        $smsNumberItems = collect($smsNumbers)->map(
            fn(SmsNumber $smsNumber) => $smsNumber->getNumber()
        );

        $steamIds = collect($userRepository->allWithSteamId())
            ->map(fn(User $user) => $user->getSteamId())
            ->join(";");

        $serverRepository->touch($server->getId(), $platform, $version);

        $data = merge_recursive(
            [
                "id" => $server->getId(),
                "license_token" => $settings->getLicenseToken(),
                "sms_platform_id" => $smsPlatformId,
                "sms_text" => $smsModule->getSmsCode(),
                "steam_ids" => "$steamIds;",
                "currency" => $settings->getCurrency(),
                "contact" => $settings->getContact(),
                "vat" => $settings->getVat(),
                "sn" => $smsNumberItems->all(),
                "se" => $serviceItems->all(),
                "pr" => $priceItems->all(),
                "pf" => $playerFlagItems->all(),
            ],
            (array) $externalConfigProvider->getConfig("server_config")
        );

        return $acceptHeader->has("application/json")
            ? new ServerJsonResponse($data)
            : new AssocResponse($data);
    }

    private function isVersionAcceptable($version, Platform $platform): bool
    {
        if (!$platform) {
            return false;
        }

        $minimumVersions = [
            Platform::AMXMODX => "3.10.0",
            Platform::SOURCEMOD => "3.9.0",
        ];

        return version_compare($version, $minimumVersions[$platform->getValue()]) >= 0;
    }
}
