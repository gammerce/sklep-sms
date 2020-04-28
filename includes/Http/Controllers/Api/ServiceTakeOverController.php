<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\Managers\ServiceModuleManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceTakeOverController
{
    public function post($service, Request $request, ServiceModuleManager $serviceModuleManager)
    {
        $serviceModule = $serviceModuleManager->get($service);

        if (!($serviceModule instanceof IServiceTakeOver)) {
            throw new InvalidServiceModuleException();
        }

        $returnData = $serviceModule->serviceTakeOver($request->request->all());

        return new ApiResponse(
            array_get($returnData, 'status'),
            array_get($returnData, 'text'),
            array_get($returnData, 'positive'),
            array_get($returnData, 'data')
        );
    }
}
