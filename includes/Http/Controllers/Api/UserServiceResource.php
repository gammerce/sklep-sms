<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\Services\UserServiceService;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceResource
{
    public function put(
        $userServiceId,
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
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

        if ($userService->getUid() !== $user->getUid()) {
            throw new EntityNotFoundException();
        }

        $serviceModule = $heart->getServiceModule($userService->getServiceId());
        if (!$serviceModule) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), false);
        }

        if (
            !$settings['user_edit_service'] ||
            !($serviceModule instanceof IServiceUserOwnServicesEdit)
        ) {
            return new ApiResponse(
                "service_cant_be_modified",
                $lang->t('service_cant_be_modified'),
                false
            );
        }

        $serviceModule->userOwnServiceEdit(
            array_merge($request->request->all(), [
                "id" => $userServiceId,
            ]),
            $userService
        );

        return new ApiResponse('ok', $lang->t('edited_user_service'), true);
    }
}
