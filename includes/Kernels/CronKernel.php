<?php
namespace App\Kernels;

use App\CronExceutor;
use App\Settings;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CronKernel extends Kernel
{
    public function handle(Request $request)
    {
        global $argv;

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var CronExceutor $cronExecutor */
        $cronExecutor = $this->app->make(CronExceutor::class);

        // Sprawdzenie random stringu
        if ($_GET['key'] != $settings['random_key'] && $argv[1] != $settings['random_key']) {
            return new Response($lang->translate('wrong_cron_key'));
        }

        $cronExecutor->run();

        return new Response();
    }
}
