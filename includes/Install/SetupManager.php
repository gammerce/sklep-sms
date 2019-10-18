<?php
namespace App\Install;

use App\Path;
use App\TranslationManager;
use App\Translator;

class SetupManager
{
    /** @var Path */
    private $path;

    /** @var Translator */
    private $lang;

    public function __construct(Path $path, TranslationManager $translationManager)
    {
        $this->path = $path;
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
        file_put_contents($this->path->to('data/setup_error'), '');
    }

    /** @return bool */
    public function hasFailed()
    {
        return file_exists($this->path->to('data/setup_error'));
    }

    /** @return bool */
    public function isInProgress()
    {
        return file_exists($this->path->to('data/setup_progress'));
    }

    private function putInProgress()
    {
        file_put_contents($this->path->to('data/setup_progress'), "");
    }

    public function removeInProgress()
    {
        unlink($this->path->to('data/setup_progress'));
    }
}
