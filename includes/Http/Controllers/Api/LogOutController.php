<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class LogOutController
{
    public function post(Request $request, TranslationManager $translationManager)
    {
        $lang = $translationManager->user();

        $request->getSession()->invalidate();

        return new ApiResponse("logged_out", $lang->translate('logout_success'), 1);
    }
}
