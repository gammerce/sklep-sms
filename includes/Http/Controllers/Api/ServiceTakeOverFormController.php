<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\System\Heart;

class ServiceTakeOverFormController
{
    public function get($service, Heart $heart)
    {
        $serviceModule = $heart->getServiceModule($service);
        if (!($serviceModule instanceof IServiceTakeOver)) {
            throw new InvalidServiceModuleException();
        }

        return new PlainResponse($serviceModule->serviceTakeOverFormGet());
    }
}
