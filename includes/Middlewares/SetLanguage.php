<?php
namespace App\Middlewares;

use App\Application;
use App\Settings;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class SetLanguage implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $app->make(TranslationManager::class);

        /** @var Settings $settings */
        $settings = $app->make(Settings::class);

        $lang = $translationManager->user();

        if (isset($_GET['language'])) {
            $lang->setLanguage($_GET['language']);
        } elseif (isset($_COOKIE['language'])) {
            $lang->setLanguage($_COOKIE['language']);
        } else {
            $details = json_decode(file_get_contents("http://ipinfo.io/" . get_ip() . "/json"));
            if (isset($details->country) && strlen($temp_lang = $lang->getLanguageByShort($details->country))) {
                $lang->setLanguage($temp_lang);
                unset($temp_lang);
            } else {
                $lang->setLanguage($settings['language']);
            }
        }

        $translationManager->shop()->setLanguage($settings['language']);

        return null;
    }
}
