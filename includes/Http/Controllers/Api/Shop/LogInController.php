<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\ApiResponse;
use App\Managers\UserManager;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class LogInController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        UserManager $userManager,
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

        $user = $userManager->getUserByLogin($username, $password);
        if ($user) {
            $auth->loginUser($request, $user);
            return new ApiResponse("logged_in", $lang->t("login_success"), true);
        }

        return new ApiResponse("not_logged", $lang->t("wrong_login_data"), false);
    }
}
