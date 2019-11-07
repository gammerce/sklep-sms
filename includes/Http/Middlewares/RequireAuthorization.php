<?php
namespace App\Http\Middlewares;

use App\System\Application;
use App\System\Auth;
use App\Http\Responses\ApiResponse;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class RequireAuthorization implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $privilege = null)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $app->make(TranslationManager::class);
        $lang = $translationManager->user();

        if (!$auth->check() || ($privilege && !get_privileges($privilege, $auth->user()))) {
            return new ApiResponse("not_logged_in", $lang->translate('not_logged_or_no_perm'), 0);
        }

        return null;
    }
}
