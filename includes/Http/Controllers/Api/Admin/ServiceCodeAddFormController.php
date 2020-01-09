<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceServiceCodeAdminManage;
use App\System\Heart;

class ServiceCodeAddFormController
{
    public function get($serviceId, Heart $heart)
    {
        $output = "";
        if (
            ($serviceModule = $heart->getServiceModule($serviceId)) !== null &&
            $serviceModule instanceof IServiceServiceCodeAdminManage
        ) {
            $output = $serviceModule->serviceCodeAdminAddFormGet();
        }

        return new PlainResponse($output);
    }
}
