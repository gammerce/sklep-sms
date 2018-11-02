<?php

namespace App\Middlewares;

use App\Application;
use App\LocaleService;
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

        /** @var LocaleService $localeService */
        $localeService = $app->make(LocaleService::class);

        $locale = $localeService->getLocale($request);

        $translationManager->user()->setLanguage($locale);
        $translationManager->shop()->setLanguage($settings['language']);

        return null;
    }
}
