<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\Translation\TranslationManager;
use App\View\ServiceModuleManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceCollection
{
    public function post(
        $serviceId,
        Request $request,
        ServiceModuleManager $serviceModuleManager,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        $serviceModule = $serviceModuleManager->get($serviceId);
        if (!($serviceModule instanceof IServiceUserServiceAdminAdd)) {
            throw new InvalidServiceModuleException();
        }

        $serviceModule->userServiceAdminAdd($request->request->all());

        return new ApiResponse("ok", $lang->t('service_added_correctly'), true);
    }
}
