<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\PlainResponse;
use App\Managers\ServiceModuleManager;
use App\ServiceModules\Interfaces\IServiceAdminManage;

class ServiceModuleExtraFieldsController
{
    public function get($serviceId, $moduleId, ServiceModuleManager $serviceModuleManager)
    {
        $output = "";

        // Pobieramy moduł obecnie edytowanej usługi, jeżeli powróciliśmy do pierwotnego modułu
        // W przeciwnym razie pobieramy wybrany moduł

        $serviceModule = $serviceModuleManager->get($serviceId);

        if ($serviceModule === null || $serviceModule->getModuleId() != $moduleId) {
            $serviceModule = $serviceModuleManager->getEmpty($moduleId);
        }

        if ($serviceModule instanceof IServiceAdminManage) {
            $output = $serviceModule->serviceAdminExtraFieldsGet();
        }

        return new PlainResponse($output);
    }
}
