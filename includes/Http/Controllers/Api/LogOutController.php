<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class LogOutController
{
    public function post(Request $request, TranslationManager $translationManager, Auth $auth)
    {
        $lang = $translationManager->user();

        if (!$auth->check()) {
            return new ApiResponse("already_logged_out");
        }

        $request->getSession()->invalidate();

        return new ApiResponse("logged_out", $lang->t('logout_success'), 1);
    }
}
