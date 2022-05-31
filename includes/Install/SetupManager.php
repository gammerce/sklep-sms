<?php
namespace App\Install;

use App\Support\FileSystemContract;
use App\Support\BasePath;

class SetupManager
{
    private BasePath $path;
    private FileSystemContract $fileSystem;

    public function __construct(BasePath $path, FileSystemContract $fileSystem)
    {
        $this->path = $path;
        $this->fileSystem = $fileSystem;
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
        $path = $this->path->to("data/setup_error");
        $this->fileSystem->put($path, "");
        $this->fileSystem->setPermissions($path, 0777);
    }

    /** @return bool */
    public function hasFailed()
    {
        return $this->fileSystem->exists($this->path->to("data/setup_error"));
    }

    /** @return bool */
    public function isInProgress()
    {
        return $this->fileSystem->exists($this->path->to("data/setup_progress"));
    }

    private function putInProgress()
    {
        $path = $this->path->to("data/setup_progress");
        $this->fileSystem->put($path, "");
        $this->fileSystem->setPermissions($path, 0777);
    }

    private function removeInProgress()
    {
        $this->fileSystem->delete($this->path->to("data/setup_progress"));
    }
}
