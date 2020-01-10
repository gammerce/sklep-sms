<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\System\Heart;

class ServiceModuleExtraFieldsController
{
    public function get($serviceId, $moduleId, Heart $heart)
    {
        $output = "";

        // Pobieramy moduł obecnie edytowanej usługi, jeżeli powróciliśmy do pierwotnego modułu
        // W przeciwnym razie pobieramy wybrany moduł

        $serviceModule = $heart->getServiceModule($serviceId);

        if ($serviceModule === null || $serviceModule->getModuleId() != $moduleId) {
            $serviceModule = $heart->getEmptyServiceModule($moduleId);
        }

        if ($serviceModule instanceof IServiceAdminManage) {
            $output = $serviceModule->serviceAdminExtraFieldsGet();
        }

        return new PlainResponse($output);
    }
}
