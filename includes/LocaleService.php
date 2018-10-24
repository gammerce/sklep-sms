<?php
namespace App;

use App\Requesting\Requester;

class LocaleService
{
    /** @var TranslationManager */
    private $translationManager;

    /** @var Settings */
    private $settings;

    /** @var Requester */
    private $requester;

    public function __construct(TranslationManager $translationManager, Settings $settings, Requester $requester)
    {
        $this->translationManager = $translationManager;
        $this->settings = $settings;
        $this->requester = $requester;
    }

    public function getLocale()
    {
        if (isset($_GET['language'])) {
            return $_GET['language'];
        }

        if (isset($_COOKIE['language'])) {
            return $_COOKIE['language'];
        }

        $response = $this->requester->get("http://ipinfo.io/" . get_ip() . "/json");

        if ($response && $response->isOk()) {
            $details = $response->json();

            if (isset($details['country'])) {
                $locale = $this->translationManager->user()->getLanguageByShort($details['country']);

                if (strlen($locale)) {
                    return $locale;
                }
            }
        }

        return $this->settings['language'];
    }
}
