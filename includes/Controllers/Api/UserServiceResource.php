<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Heart;
use App\Responses\ApiResponse;
use App\Services\Interfaces\IServiceUserOwnServicesEdit;
use App\Settings;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceResource
{
    public function put(
        $userServiceId,
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        Auth $auth,
        Settings $settings
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        if (!is_logged()) {
            return new ApiResponse("not_logged", $lang->translate('not_logged'), 0);
        }

        $userService = get_users_services($userServiceId);

        // User service was not found
        if (empty($userService)) {
            return new ApiResponse("dont_play_games", $lang->translate('dont_play_games'), 0);
        }

        // User is not an owner of the userService
        if ($userService['uid'] != $user->getUid()) {
            return new ApiResponse("dont_play_games", $lang->translate('dont_play_games'), 0);
        }

        if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
            return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
        }

        if (
            !$settings['user_edit_service'] ||
            !($serviceModule instanceof IServiceUserOwnServicesEdit)
        ) {
            return new ApiResponse(
                "service_cant_be_modified",
                $lang->translate('service_cant_be_modified'),
                0
            );
        }

        $returnData = $serviceModule->userOwnServiceEdit(
            array_merge($request->request->all(), [
                "id" => $userServiceId,
            ]),
            $userService
        );

        if ($returnData['status'] == "warnings") {
            $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
        }

        return new ApiResponse(
            $returnData['status'],
            $returnData['text'],
            $returnData['positive'],
            $returnData['data']
        );
    }
}
