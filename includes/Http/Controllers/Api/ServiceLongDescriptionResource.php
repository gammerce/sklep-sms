<?php
namespace App\Http\Controllers\Api;

use App\View\ServiceModuleManager;
use Symfony\Component\HttpFoundation\Response;

class ServiceLongDescriptionResource
{
    public function get($serviceId, ServiceModuleManager $serviceModuleManager)
    {
        $serviceModule = $serviceModuleManager->get($serviceId);
        return new Response($serviceModule ? $serviceModule->descriptionLongGet() : '');
    }
}
