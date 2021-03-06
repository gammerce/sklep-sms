<?php
namespace App\Http\Middlewares;

use App\Http\Responses\ApiResponse;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireUnauthorized implements MiddlewareContract
{
    private Auth $auth;
    private Translator $lang;

    public function __construct(Auth $auth, TranslationManager $translationManager)
    {
        $this->auth = $auth;
        $this->lang = $translationManager->user();
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        if ($this->auth->check()) {
            return new ApiResponse("logged_in", $this->lang->t("logged"), 0);
        }

        return $next($request);
    }
}
