<?php
namespace App\Http\Controllers\Api;

use App\Auth;
use App\Heart;
use App\Http\Responses\HtmlResponse;
use App\Services\Interfaces\IServiceUserOwnServicesEdit;
use App\Settings;
use App\Template;
use App\TranslationManager;

class UserServiceEditFormController
{
    public function get(
        $userServiceId,
        TranslationManager $translationManager,
        Settings $settings,
        Auth $auth,
        Heart $heart,
        Template $template
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        if (!is_logged()) {
            return new HtmlResponse($lang->translate('service_cant_be_modified'));
        }

        // Użytkownik nie może edytować usługi
        if (!$settings['user_edit_service']) {
            return new HtmlResponse($lang->translate('not_logged'));
        }

        $userService = get_users_services($userServiceId);

        if (empty($userService)) {
            return new HtmlResponse($lang->translate('dont_play_games'));
        }

        // Dany użytkownik nie jest właścicielem usługi o danym id
        if ($userService['uid'] != $user->getUid()) {
            return new HtmlResponse($lang->translate('dont_play_games'));
        }

        if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
            return new HtmlResponse($lang->translate('service_cant_be_modified'));
        }

        if (
            !$settings['user_edit_service'] ||
            !($serviceModule instanceof IServiceUserOwnServicesEdit)
        ) {
            return new HtmlResponse($lang->translate('service_cant_be_modified'));
        }

        $buttons = $template->render("services/my_services_savencancel");

        return new HtmlResponse($buttons . $serviceModule->userOwnServiceEditFormGet($userService));
    }
}
