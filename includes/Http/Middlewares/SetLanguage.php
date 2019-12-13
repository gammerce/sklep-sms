<?php

namespace App\Http\Middlewares;

use App\LocaleService;
use App\System\Application;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class SetLanguage implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $app->make(TranslationManager::class);

        /** @var Settings $settings */
        $settings = $app->make(Settings::class);

        /** @var LocaleService $localeService */
        $localeService = $app->make(LocaleService::class);

        $locale = $localeService->getLocale($request);

        $translationManager->user()->setLanguage($locale);
        $translationManager->shop()->setLanguage($settings['language']);

        return null;
    }
}
