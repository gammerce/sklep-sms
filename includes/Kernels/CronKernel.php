<?php
namespace App\Kernels;

use App\System\CronExecutor;
use App\Http\Middlewares\IsUpToDate;
use App\Http\Middlewares\LoadSettings;
use App\Http\Middlewares\SetLanguage;
use App\Http\Middlewares\SetUserSession;
use App\Http\Middlewares\ValidateLicense;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CronKernel extends Kernel
{
    protected $middlewares = [
        SetUserSession::class,
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ValidateLicense::class,
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
        if (
            $request->query->get('key') != $settings['random_key'] &&
            $argv[1] != $settings['random_key']
        ) {
            return new Response($lang->translate('wrong_cron_key'));
        }

        $cronExecutor->run();

        return new Response();
    }
}
