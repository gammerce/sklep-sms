<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class LogInController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        Auth $auth
    ) {
        if ($auth->check()) {
            return new ApiResponse("already_logged_in");
        }

        $lang = $translationManager->user();

        $username = $request->request->get("username");
        $password = $request->request->get("password");

        if (!$username || !$password) {
            return new ApiResponse("no_data", $lang->t("no_login_password"), false);
        }

        $user = $heart->getUserByLogin($username, $password);
        if ($user) {
            $auth->loginUser($request, $user);
            return new ApiResponse("logged_in", $lang->t("login_success"), true);
        }

        return new ApiResponse("not_logged", $lang->t("wrong_login_data"), false);
    }
}
