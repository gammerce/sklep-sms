<?php
namespace App\Http\Services;

use App\Http\Validation\Rules\ArrayRule;
use App\Http\Validation\Rules\GroupsRule;
use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\IterateRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Validator;
use App\Managers\ServerManager;
use App\Models\Server;
use App\Service\ServerServiceService;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\ServiceModule;

class ServiceService
{
    private ServerServiceService $serverServiceService;
    private ServerManager $serverManager;

    public function __construct(
        ServerServiceService $serverServiceService,
        ServerManager $serverManager
    ) {
        $this->serverServiceService = $serverServiceService;
        $this->serverManager = $serverManager;
    }

    public function extendValidator(Validator $validator, ServiceModule $serviceModule = null)
    {
        $validator->extendData([
            "groups" => $validator->getData("groups") ?: [],
            "order" => trim($validator->getData("order")),
        ]);

        $validator->extendRules([
            "groups" => [new ArrayRule(), new GroupsRule()],
            "name" => [new RequiredRule()],
            "order" => [new IntegerRule()],
            "short_description" => [new MaxLengthRule(28)],
            "description" => [],
            "tag" => [],
            "server_ids" => [new ArrayRule(), new IterateRule(new ServerExistsRule())],
        ]);

        if ($serviceModule instanceof IServiceAdminManage) {
            $serviceModule->serviceAdminManagePre($validator);
        }

        return $validator;
    }

    public function updateServiceServerLinks($serviceId, array $serverIds)
    {
        $serversServices = collect($this->serverManager->all())
            ->map(function (Server $server) use ($serviceId, $serverIds) {
                return [
                    "service_id" => $serviceId,
                    "server_id" => $server->getId(),
                    "connect" => in_array($server->getId(), $serverIds),
                ];
            })
            ->all();

        $this->serverServiceService->updateLinks($serversServices);
    }
}
