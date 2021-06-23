<?php
namespace App\Translation;

use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class LocaleService
{
    private Settings $settings;
    private LocaleCookieService $localeCookieService;

    public function __construct(LocaleCookieService $localeCookieService, Settings $settings)
    {
        $this->settings = $settings;
        $this->localeCookieService = $localeCookieService;
    }

    public function getLocale(Request $request): string
    {
        $queryLocale = $this->resolveLocale($request->query->get("language"));
        if ($queryLocale) {
            return $queryLocale;
        }

        $cookieLocale = $this->localeCookieService->getLocale($request);
        if ($cookieLocale) {
            return $cookieLocale;
        }

        $headerLocale = $this->getLocaleFromHeader($request);
        if ($headerLocale) {
            return $headerLocale;
        }

        return $this->settings->getLanguage();
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    private function resolveLocale($locale): ?string
    {
        if (Translator::languageExists($locale)) {
            return $locale;
        }

        $locale = Translator::getLanguageByShort($locale);
        if (Translator::languageExists($locale)) {
            return $locale;
        }

        return null;
    }

    /**
     * @param Request $request
     * @return string|null
     */
    private function getLocaleFromHeader(Request $request): ?string
    {
        foreach ($request->getLanguages() as $shortLocale) {
            $locale = Translator::getLanguageByShort($shortLocale);
            if ($locale) {
                return $locale;
            }
        }

        return null;
    }
}
