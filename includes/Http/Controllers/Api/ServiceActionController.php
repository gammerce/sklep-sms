<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\Managers\ServiceModuleManager;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use Symfony\Component\HttpFoundation\Request;

class ServiceActionController
{
    public function post(
        $service,
        $action,
        Request $request,
        ServiceModuleManager $serviceModuleManager
    ) {
        $serviceModule = $serviceModuleManager->get($service);

        if (!($serviceModule instanceof IServiceActionExecute)) {
            throw new InvalidServiceModuleException();
        }

        return new PlainResponse($serviceModule->actionExecute($action, $request->request->all()));
    }
}
