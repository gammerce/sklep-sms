<?php
namespace App\Install;

use App\Support\FileSystemContract;
use App\Support\BasePath;

class OldShop
{
    private BasePath $path;
    private FileSystemContract $fileSystem;

    public function __construct(BasePath $path, FileSystemContract $fileSystem)
    {
        $this->path = $path;
        $this->fileSystem = $fileSystem;
    }

    public function hasConfigFile()
    {
        return $this->fileSystem->exists($this->path->to("/includes/config.php"));
    }
}
