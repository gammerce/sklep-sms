<?php
namespace App\Kernels;

use App\CronExecutor;
use App\Middlewares\DecodeGetAttributes;
use App\Middlewares\IsUpToDate;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\SetLanguage;
use App\Settings;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CronKernel extends Kernel
{
    protected $middlewares = [
        DecodeGetAttributes::class,
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAuthentication::class,
        LicenseIsValid::class,
    ];

    public function run(Request $request)
    {
        global $argv;

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var CronExecutor $cronExecutor */
        $cronExecutor = $this->app->make(CronExecutor::class);

        // Sprawdzenie random stringu
        if ($_GET['key'] != $settings['random_key'] && $argv[1] != $settings['random_key']) {
            return new Response($lang->translate('wrong_cron_key'));
        }

        $cronExecutor->run();

        return new Response();
    }
}
