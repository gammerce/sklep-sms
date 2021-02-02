<?php
namespace App\Translation;

use App\Requesting\Requester;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class LocaleService
{
    private TranslationManager $translationManager;
    private Settings $settings;
    private Requester $requester;
    private LocaleCookieService $localeCookieService;

    public function __construct(
        TranslationManager $translationManager,
        LocaleCookieService $localeCookieService,
        Settings $settings,
        Requester $requester
    ) {
        $this->translationManager = $translationManager;
        $this->settings = $settings;
        $this->requester = $requester;
        $this->localeCookieService = $localeCookieService;
    }

    public function getLocale(Request $request)
    {
        $queryLocale = $this->resolveLocale($request->query->get("language"));
        if ($queryLocale) {
            return $queryLocale;
        }

        $cookieLocale = $this->localeCookieService->getLocale($request);
        if ($cookieLocale) {
            return $cookieLocale;
        }

        $locale = $this->getLocaleFromHeader($request);
        if ($locale) {
            return $locale;
        }

        return $this->settings->getLanguage();
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    private function resolveLocale($locale)
    {
        $translator = $this->translationManager->user();

        if ($translator->languageExists($locale)) {
            return $locale;
        }

        $locale = $translator->getLanguageByShort($locale);
        if ($translator->languageExists($locale)) {
            return $locale;
        }

        return null;
    }

    /**
     * @param Request $request
     * @return string|null
     */
    private function getLocaleFromHeader(Request $request)
    {
        $translator = $this->translationManager->user();

        foreach ($request->getLanguages() as $shortLocale) {
            $locale = $translator->getLanguageByShort($shortLocale);
            if ($locale) {
                return $locale;
            }
        }

        return null;
    }
}
