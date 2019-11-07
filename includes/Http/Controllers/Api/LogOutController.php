<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class LogOutController
{
    public function post(Request $request, TranslationManager $translationManager)
    {
        $lang = $translationManager->user();

        if (!is_logged()) {
            return new ApiResponse("already_logged_out");
        }

        $request->getSession()->invalidate();

        return new ApiResponse("logged_out", $lang->translate('logout_success'), 1);
    }
}
