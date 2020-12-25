<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\HtmlResponse;
use App\Http\Responses\PlainResponse;
use App\Managers\ServiceModuleManager;
use App\Service\UserServiceService;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\Support\Template;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;

class UserServiceEditFormController
{
    public function get(
        $userServiceId,
        TranslationManager $translationManager,
        Settings $settings,
        Auth $auth,
        ServiceModuleManager $serviceModuleManager,
        Template $template,
        UserServiceService $userServiceService
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        if (!$settings["user_edit_service"]) {
            return new PlainResponse($lang->t("not_logged"));
        }

        $userService = $userServiceService->findOne($userServiceId);

        if (!$userService) {
            return new PlainResponse($lang->t("dont_play_games"));
        }

        if ($userService->getUserId() !== $user->getId()) {
            return new PlainResponse($lang->t("dont_play_games"));
        }

        $serviceModule = $serviceModuleManager->get($userService->getServiceId());
        if (!($serviceModule instanceof IServiceUserOwnServicesEdit)) {
            return new PlainResponse($lang->t("service_cant_be_modified"));
        }

        $buttons = $template->render("shop/components/user_own_services/savencancel");

        return new HtmlResponse($buttons . $serviceModule->userOwnServiceEditFormGet($userService));
    }
}
