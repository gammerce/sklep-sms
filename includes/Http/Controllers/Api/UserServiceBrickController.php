<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\HtmlResponse;
use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\Services\UserServiceService;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\View\ServiceModuleManager;

class UserServiceBrickController
{
    public function get(
        $userServiceId,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings,
        ServiceModuleManager $serviceModuleManager,
        UserServiceService $userServiceService
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $userService = $userServiceService->findOne($userServiceId);

        if (!$userService) {
            return new PlainResponse($lang->t('dont_play_games'));
        }

        if ($userService->getUid() !== $user->getUid()) {
            return new PlainResponse($lang->t('dont_play_games'));
        }

        $serviceModule = $serviceModuleManager->get($userService->getServiceId());
        if (!($serviceModule instanceof IServiceUserOwnServices)) {
            return new PlainResponse($lang->t('service_not_displayed'));
        }

        $buttonEdit = "";
        if (
            $settings['user_edit_service'] &&
            $serviceModule instanceof IServiceUserOwnServicesEdit
        ) {
            $buttonEdit = create_dom_element("button", $lang->t('edit'), [
                'class' => "button is-small edit_row",
                'type' => 'button',
            ]);
        }

        return new HtmlResponse($serviceModule->userOwnServiceInfoGet($userService, $buttonEdit));
    }
}
