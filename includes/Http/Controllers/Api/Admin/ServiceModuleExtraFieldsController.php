<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\PlainResponse;
use App\Services\Interfaces\IServiceAdminManage;
use App\System\Heart;

class ServiceModuleExtraFieldsController
{
    public function get($serviceId, $moduleId, Heart $heart)
    {
        $output = "";
        // Pobieramy moduł obecnie edytowanej usługi, jeżeli powróciliśmy do pierwotnego modułu
        // W przeciwnym razie pobieramy wybrany moduł
        if (
            is_null($serviceModule = $heart->getServiceModule($serviceId)) ||
            $serviceModule->getModuleId() != $moduleId
        ) {
            $serviceModule = $heart->getServiceModuleS($moduleId);
        }

        if ($serviceModule !== null && $serviceModule instanceof IServiceAdminManage) {
            $output = $serviceModule->serviceAdminExtraFieldsGet();
        }

        return new PlainResponse($output);
    }
}
