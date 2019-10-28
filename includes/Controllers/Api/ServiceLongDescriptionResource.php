<?php
namespace App\Controllers\Api;

use App\Heart;
use App\Responses\PlainResponse;

class ServiceLongDescriptionResource
{
    public function get($serviceId, Heart $heart)
    {
        $output = "";

        if (($serviceModule = $heart->getServiceModule($serviceId)) !== null) {
            $output = $serviceModule->descriptionLongGet();
        }

        return new PlainResponse($output);
    }
}
