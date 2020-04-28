<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\Managers\ServiceModuleManager;

class ServiceTakeOverFormController
{
    public function get($service, ServiceModuleManager $serviceModuleManager)
    {
        $serviceModule = $serviceModuleManager->get($service);
        if (!($serviceModule instanceof IServiceTakeOver)) {
            throw new InvalidServiceModuleException();
        }

        return new PlainResponse($serviceModule->serviceTakeOverFormGet());
    }
}
