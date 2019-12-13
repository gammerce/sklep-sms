<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\PlainResponse;
use App\System\Heart;

class UserServiceAddFormController
{
    public function get($serviceId, Heart $heart)
    {
        $output = "";
        if (($serviceModule = $heart->getServiceModule($serviceId)) !== null) {
            $output = $serviceModule->userServiceAdminAddFormGet();
        }

        return new PlainResponse($output);
    }
}
