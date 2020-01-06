<?php
namespace App\Install;

use App\System\Path;

class SetupManager
{
    /** @var Path */
    private $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function start()
    {
        $this->putInProgress();
    }

    public function finish()
    {
        $this->removeInProgress();
    }

    // TODO It should be used in exception handler
    public function markAsFailed()
    {
        $path = $this->path->to('data/setup_error');
        file_put_contents($path, "");
        chmod($path, 0777);
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
        $path = $this->path->to('data/setup_progress');
        file_put_contents($path, "");
        chmod($path, 0777);
    }

    private function removeInProgress()
    {
        unlink($this->path->to('data/setup_progress'));
    }
}
