<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\System\Heart;
use Symfony\Component\HttpFoundation\Request;

class ServiceActionController
{
    public function post($service, $action, Request $request, Heart $heart)
    {
        $serviceModule = $heart->getServiceModule($service);

        if (!($serviceModule instanceof IServiceActionExecute)) {
            throw new InvalidServiceModuleException();
        }

        return new PlainResponse($serviceModule->actionExecute($action, $request->request->all()));
    }
}
