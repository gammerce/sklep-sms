<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\Loggers\DatabaseLogger;
use App\Managers\ServiceModuleManager;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceCollection
{
    public function post(
        $serviceId,
        Request $request,
        DatabaseLogger $logger,
        ServiceModuleManager $serviceModuleManager,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        $serviceModule = $serviceModuleManager->get($serviceId);
        if (!($serviceModule instanceof IServiceUserServiceAdminAdd)) {
            throw new InvalidServiceModuleException();
        }

        $boughtServiceId = $serviceModule->userServiceAdminAdd($request);
        $logger->logWithActor("log_user_service_added", $boughtServiceId);

        return new ApiResponse("ok", $lang->t("service_added_correctly"), true);
    }
}
