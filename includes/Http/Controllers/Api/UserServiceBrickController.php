<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\HtmlResponse;
use App\Services\Interfaces\IServiceUserOwnServices;
use App\Services\Interfaces\IServiceUserOwnServicesEdit;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;

class UserServiceBrickController
{
    public function get(
        $userServiceId,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings,
        Heart $heart
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $userService = get_users_services($userServiceId);

        // Brak takiej usługi w bazie
        if (empty($userService)) {
            return new HtmlResponse($lang->translate('dont_play_games'));
        }

        // Dany użytkownik nie jest właścicielem usługi o danym id
        if ($userService['uid'] != $user->getUid()) {
            return new HtmlResponse($lang->translate('dont_play_games'));
        }

        if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
            return new HtmlResponse($lang->translate('service_not_displayed'));
        }

        if (!($serviceModule instanceof IServiceUserOwnServices)) {
            return new HtmlResponse($lang->translate('service_not_displayed'));
        }

        $buttonEdit = "";
        if (
            $settings['user_edit_service'] &&
            $serviceModule instanceof IServiceUserOwnServicesEdit
        ) {
            $buttonEdit = create_dom_element("button", $lang->translate('edit'), [
                'class' => "button is-small edit_row",
                'type' => 'button',
            ]);
        }

        return new HtmlResponse($serviceModule->userOwnServiceInfoGet($userService, $buttonEdit));
    }
}
