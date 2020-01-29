<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\System\Heart;
use Symfony\Component\HttpFoundation\Request;

class ServiceTakeOverController
{
    public function post($service, Request $request, Heart $heart)
    {
        $serviceModule = $heart->getServiceModule($service);

        if (!($serviceModule instanceof IServiceTakeOver)) {
            throw new InvalidServiceModuleException();
        }

        $returnData = $serviceModule->serviceTakeOver($request->request->all());

        if ($returnData['status'] == "warnings") {
            $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
        }

        return new ApiResponse(
            $returnData['status'],
            $returnData['text'],
            $returnData['positive'],
            $returnData['data']
        );
    }
}
