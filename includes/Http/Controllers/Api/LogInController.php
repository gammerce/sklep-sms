<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\User\UserActivityService;
use Symfony\Component\HttpFoundation\Request;

class LogInController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        UserActivityService $activityService,
        Heart $heart,
        Auth $auth
    ) {
        if ($auth->check()) {
            return new ApiResponse("already_logged_in");
        }

        $lang = $translationManager->user();
        $session = $request->getSession();

        $username = $request->request->get("username");
        $password = $request->request->get("password");

        if (!$username || !$password) {
            return new ApiResponse("no_data", $lang->t('no_login_password'), 0);
        }

        $user = $heart->getUserByLogin($username, $password);
        if ($user->exists()) {
            $session->set("uid", $user->getUid());
            $activityService->update($user);
            return new ApiResponse("logged_in", $lang->t('login_success'), 1);
        }

        return new ApiResponse("not_logged", $lang->t('bad_pass_nick'), 0);
    }
}
