<?php
namespace App\Controllers\Api;

use App\Heart;
use App\Responses\ApiResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class LogInController
{
    public function post(Request $request, TranslationManager $translationManager, Heart $heart)
    {
        if (is_logged()) {
            return new ApiResponse("already_logged_in");
        }

        $lang = $translationManager->user();
        $session = $request->getSession();

        $username = $request->request->get("username");
        $password = $request->request->get("password");

        if (!$username || !$password) {
            return new ApiResponse("no_data", $lang->translate('no_login_password'), 0);
        }

        $user = $heart->getUser(0, $username, $password);
        if ($user->exists()) {
            $session->set("uid", $user->getUid());
            $user->updateActivity();
            return new ApiResponse("logged_in", $lang->translate('login_success'), 1);
        }

        return new ApiResponse("not_logged", $lang->translate('bad_pass_nick'), 0);
    }
}
