<?php
namespace App\Controllers\Api\Admin;

use App\Heart;
use App\Responses\PlainResponse;

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
