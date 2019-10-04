<?php
namespace App\Install;

use App\Application;
use App\TranslationManager;
use App\Translator;

class InstallManager
{
    /** @var Application */
    private $app;

    /** @var Translator */
    private $lang;

    public function __construct(Application $app, TranslationManager $translationManager)
    {
        $this->app = $app;
        $this->lang = $translationManager->user();
    }

    public function start()
    {
        $this->putInProgress();
    }

    public function finish()
    {
        $this->removeInProgress();
    }

    public function markAsFailed()
    {
        file_put_contents($this->app->path('data/install_error'), '');
    }

    /** @return bool */
    public function hasFailed()
    {
        return file_exists($this->app->path('data/install_error'));
    }

    /** @return bool */
    public function isInProgress()
    {
        return file_exists($this->app->path('data/install_progress'));
    }

    private function putInProgress()
    {
        file_put_contents($this->app->path('data/install_progress'), "");
    }

    public function removeInProgress()
    {
        unlink($this->app->path('data/install_progress'));
    }
}
