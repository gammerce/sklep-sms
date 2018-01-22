<?php
namespace App\Providers;

use App\License;
use App\Settings;
use App\TranslationManager;

class AppServiceProvider
{
    public function boot(Settings $settings, TranslationManager $translationManager, License $license)
    {
        $settings->load();
        $translationManager->shop()->setLanguage($settings['language']);
        $license->validate();
    }
}
