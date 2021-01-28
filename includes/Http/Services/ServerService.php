<?php
namespace App\Http\Services;

use App\Http\Validation\Rules\ArrayRule;
use App\Http\Validation\Rules\DefaultSmsPlatformRule;
use App\Http\Validation\Rules\IterateRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServiceExistsRule;
use App\Http\Validation\Rules\SupportSmsRule;
use App\Http\Validation\Rules\SupportTransferRule;
use App\Http\Validation\Validator;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\Service\ServerServiceService;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;

class ServerService
{
    private ServerServiceService $serverServiceService;
    private ServiceModuleManager $serviceModuleManager;
    private ServiceManager $serviceManager;

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
                "service_ids" => array_get($body, "service_ids"),
                "sms_platform" => as_int(array_get($body, "sms_platform")),
                "transfer_platform" => array_get($body, "transfer_platform"),
            ]),
            [
                "name" => [new RequiredRule()],
                "ip" => [new RequiredRule()],
                "port" => [new RequiredRule()],
                "service_ids" => [new ArrayRule(), new IterateRule(new ServiceExistsRule())],
                "sms_platform" => [new SupportSmsRule(), new DefaultSmsPlatformRule()],
                "transfer_platform" => [new ArrayRule(), new SupportTransferRule()],
            ]
        );
    }

    public function updateServerServiceLinks($serverId, array $serviceIds)
    {
        $serversServices = collect($this->serviceManager->all())
            ->filter(
                fn(Service $service) => $this->serviceModuleManager->get(
                    $service->getId()
                ) instanceof IServicePurchaseExternal
            )
            ->map(
                fn(Service $service) => [
                    "service_id" => $service->getId(),
                    "server_id" => $serverId,
                    "connect" => in_array($service->getId(), $serviceIds, true),
                ]
            )
            ->all();

        $this->serverServiceService->updateLinks($serversServices);
    }
}
