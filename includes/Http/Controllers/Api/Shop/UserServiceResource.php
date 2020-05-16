<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\Managers\ServiceModuleManager;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\Services\UserServiceService;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceResource
{
    public function put(
        $userServiceId,
        Request $request,
        TranslationManager $translationManager,
        ServiceModuleManager $serviceModuleManager,
        Auth $auth,
        Settings $settings,
        UserServiceService $userServiceService
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $userService = $userServiceService->findOne($userServiceId);
        if (!$userService) {
            throw new EntityNotFoundException();
        }

        if ($userService->getUserId() !== $user->getId()) {
            throw new EntityNotFoundException();
        }

        $serviceModule = $serviceModuleManager->get($userService->getServiceId());
        if (!$serviceModule) {
            throw new InvalidServiceModuleException();
        }

        if (
            !$settings["user_edit_service"] ||
            !($serviceModule instanceof IServiceUserOwnServicesEdit)
        ) {
            return new ApiResponse(
                "service_cant_be_modified",
                $lang->t("service_cant_be_modified"),
                false
            );
        }

        $result = $serviceModule->userOwnServiceEdit(
            array_merge($request->request->all(), [
                "id" => $userServiceId,
            ]),
            $userService
        );

        if (is_array($result)) {
            return new ApiResponse(
                array_get($result, "status"),
                array_get($result, "text"),
                array_get($result, "positive"),
                array_get($result, "data", [])
            );
        }

        return new ApiResponse("ok", $lang->t("edited_user_service"), true);
    }
}
