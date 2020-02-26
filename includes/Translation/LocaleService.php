<?php
namespace App\Translation;

use App\Requesting\Requester;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class LocaleService
{
    /** @var TranslationManager */
    private $translationManager;

    /** @var Settings */
    private $settings;

    /** @var Requester */
    private $requester;

    /** @var LocaleCookieService */
    private $localeCookieService;

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
        $queryLocale = $request->query->get('language');
        if ($queryLocale) {
            return $queryLocale;
        }

        $cookieLocale = $this->localeCookieService->getLocale($request);
        if ($cookieLocale) {
            return $cookieLocale;
        }

        $locale = $this->getLocaleFromIp($request);
        if ($locale) {
            return $locale;
        }

        return $this->settings->getLanguage();
    }

    private function getLocaleFromIp(Request $request)
    {
        $ip = get_ip($request);
        $response = $this->requester->get("https://ipinfo.io/{$ip}/json", [], [], 2);

        if ($response && $response->isOk()) {
            $details = $response->json();

            if (isset($details['country'])) {
                $country = $this->mapCountry($details['country']);
                $locale = $this->translationManager->user()->getLanguageByShort($country);

                if (strlen($locale)) {
                    return $locale;
                }
            }
        }

        return null;
    }

    private function mapCountry($country)
    {
        $mapping = [
            'us' => 'en',
            'gb' => 'en',
        ];

        return array_get($mapping, strtolower($country), $country);
    }
}
