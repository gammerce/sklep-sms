<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\HtmlResponse;
use App\Http\Responses\PlainResponse;
use App\Managers\ServiceModuleManager;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\Services\UserServiceService;
use App\Support\Template;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;

class UserServiceBrickController
{
    public function get(
        $userServiceId,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings,
        ServiceModuleManager $serviceModuleManager,
        UserServiceService $userServiceService,
        Template $template
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $userService = $userServiceService->findOne($userServiceId);

        if (!$userService) {
            return new PlainResponse($lang->t('dont_play_games'));
        }

        if ($userService->getUserId() !== $user->getId()) {
            return new PlainResponse($lang->t('dont_play_games'));
        }

        $serviceModule = $serviceModuleManager->get($userService->getServiceId());
        if (!($serviceModule instanceof IServiceUserOwnServices)) {
            return new PlainResponse($lang->t('service_not_displayed'));
        }

        if (
            $settings['user_edit_service'] &&
            $serviceModule instanceof IServiceUserOwnServicesEdit
        ) {
            $buttonEdit = $template->render("shop/components/user_own_services/edit_button");
        } else {
            $buttonEdit = "";
        }

        return new HtmlResponse($serviceModule->userOwnServiceInfoGet($userService, $buttonEdit));
    }
}
