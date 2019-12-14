<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\System\Heart;

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
