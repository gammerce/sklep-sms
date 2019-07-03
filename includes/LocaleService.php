<?php
namespace App;

use App\Requesting\Requester;
use Symfony\Component\HttpFoundation\Request;

class LocaleService
{
    /** @var TranslationManager */
    private $translationManager;

    /** @var Settings */
    private $settings;

    /** @var Requester */
    private $requester;

    public function __construct(
        TranslationManager $translationManager,
        Settings $settings,
        Requester $requester
    ) {
        $this->translationManager = $translationManager;
        $this->settings = $settings;
        $this->requester = $requester;
    }

    public function getLocale(Request $request)
    {
        if ($request->query->has('language')) {
            return $request->query->get('language');
        }

        if ($request->cookies->has('language')) {
            return $request->cookies->get('language');
        }

        $ip = get_ip();
        $response = $this->requester->get("http://ipinfo.io/{$ip}/json");

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

        return $this->settings['language'];
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
