<?php
namespace App\Http\Middlewares;

use App\Http\Responses\ApiResponse;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class RequireUnauthorized implements MiddlewareContract
{
    /** @var Auth */
    private $auth;

    /** @var Translator */
    private $lang;

    public function __construct(Auth $auth, TranslationManager $translationManager)
    {
        $this->auth = $auth;
        $this->lang = $translationManager->user();
    }

    public function handle(Request $request, $args, Closure $next)
    {
        if ($this->auth->check()) {
            return new ApiResponse("logged_in", $this->lang->t('logged'), 0);
        }

        return $next($request);
    }
}
