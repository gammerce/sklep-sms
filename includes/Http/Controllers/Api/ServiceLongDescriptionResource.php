<?php
namespace App\Http\Controllers\Api;

use App\System\Heart;
use Symfony\Component\HttpFoundation\Response;

class ServiceLongDescriptionResource
{
    public function get($serviceId, Heart $heart)
    {
        $serviceModule = $heart->getServiceModule($serviceId);
        return new Response($serviceModule ? $serviceModule->descriptionLongGet() : '');
    }
}
