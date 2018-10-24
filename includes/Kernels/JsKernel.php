<?php
namespace App\Kernels;

use App\Middlewares\IsUpToDate;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\SetLanguage;
use App\Settings;
use App\Template;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsKernel extends Kernel
{
    protected $middlewares = [
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAuthentication::class,
        LicenseIsValid::class,
    ];

    public function run(Request $request)
    {
        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $output = '';

        if ($_GET['script'] == "language") {
            $output = $template->render2(
                "js/language.js", compact('lang', 'settings'), true, false
            );
        }

        return new Response($output, 200, [
            'Content-type' => 'text/plain; charset="UTF-8"',
        ]);
    }
}
