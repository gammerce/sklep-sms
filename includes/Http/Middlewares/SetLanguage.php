<?php
namespace App\Http\Middlewares;

use App\System\Settings;
use App\Translation\LocaleCookieService;
use App\Translation\LocaleService;
use App\Translation\TranslationManager;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLanguage implements MiddlewareContract
{
    private Settings $settings;
    private LocaleService $localeService;
    private TranslationManager $translationManager;
    private LocaleCookieService $localeCookieService;

    public function __construct(
        TranslationManager $translationManager,
        Settings $settings,
        LocaleService $localeService,
        LocaleCookieService $localeCookieService
    ) {
        $this->settings = $settings;
        $this->localeService = $localeService;
        $this->translationManager = $translationManager;
        $this->localeCookieService = $localeCookieService;
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        $locale = $this->localeService->getLocale($request);

        $this->translationManager->user()->setLanguage($locale);
        $this->translationManager->shop()->setLanguage($this->settings->getLanguage());

        /** @var Response $response */
        $response = $next($request);

        if ($this->localeCookieService->getLocale($request) !== $locale) {
            $this->localeCookieService->setLocale($response, $locale);
        }

        return $response;
    }
}
