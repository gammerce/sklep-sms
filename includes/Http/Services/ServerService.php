<?php
namespace App\Http\Services;

use App\Http\Validation\Rules\ArrayRule;
use App\Http\Validation\Rules\DefaultSmsPlatformRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SupportSmsRule;
use App\Http\Validation\Rules\SupportTransferRule;
use App\Http\Validation\Validator;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\Services\ServerServiceService;

class ServerService
{
    /** @var ServerServiceService */
    private $serverServiceService;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var ServiceManager */
    private $serviceManager;

    public function __construct(
        ServiceManager $serviceManager,
        ServiceModuleManager $serviceModuleManager,
        ServerServiceService $serverServiceService
    ) {
        $this->serverServiceService = $serverServiceService;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->serviceManager = $serviceManager;
    }

    public function createValidator(array $body)
    {
        return new Validator(
            array_merge($body, [
                "ip" => trim(array_get($body, "ip")),
                "port" => trim(array_get($body, "port")),
                "sms_platform" => as_int(array_get($body, "sms_platform")),
                "transfer_platform" => array_get($body, "transfer_platform"),
            ]),
            [
                "name" => [new RequiredRule()],
                "ip" => [new RequiredRule()],
                "port" => [new RequiredRule()],
                "sms_platform" => [new SupportSmsRule(), new DefaultSmsPlatformRule()],
                "transfer_platform" => [new ArrayRule(), new SupportTransferRule()],
            ]
        );
    }

    public function updateServerServiceAffiliations($serverId, array $body)
    {
        $serversServices = collect($this->serviceManager->getServices())
            ->filter(function (Service $service) {
                return $this->serviceModuleManager->get($service->getId()) instanceof
                    IServicePurchaseExternal;
            })
            ->map(function (Service $service) use ($serverId, $body) {
                return [
                    "service_id" => $service->getId(),
                    "server_id" => $serverId,
                    "connect" => (bool) array_get($body, $service->getId()),
                ];
            })
            ->all();

        $this->serverServiceService->updateAffiliations($serversServices);
    }
}
