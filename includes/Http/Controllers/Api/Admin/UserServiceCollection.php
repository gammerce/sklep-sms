<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\Managers\ServiceModuleManager;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\Translation\TranslationManager;
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

        $serviceModule->userServiceAdminAdd($request);

        return new ApiResponse("ok", $lang->t("service_added_correctly"), true);
    }
}
