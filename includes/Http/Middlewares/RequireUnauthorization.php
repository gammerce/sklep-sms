<?php
namespace App\Http\Middlewares;

use App\Http\Responses\ApiResponse;
use App\System\Application;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class RequireUnauthorization implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $app->make(TranslationManager::class);
        $lang = $translationManager->user();

        if ($auth->check()) {
            return new ApiResponse("logged_in", $lang->t('logged'), 0);
        }

        return null;
    }
}
