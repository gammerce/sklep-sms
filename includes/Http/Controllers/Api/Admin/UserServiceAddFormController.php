<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\View\ServiceModuleManager;

class UserServiceAddFormController
{
    public function get($serviceId, ServiceModuleManager $serviceModuleManager)
    {
        $serviceModule = $serviceModuleManager->get($serviceId);

        $output = "";
        if ($serviceModule instanceof IServiceUserServiceAdminAdd) {
            $output = $serviceModule->userServiceAdminAddFormGet();
        }

        return new PlainResponse($output);
    }
}
