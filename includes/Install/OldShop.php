<?php
namespace App\Install;

use App\Support\FileSystemContract;
use App\Support\Path;

class OldShop
{
    private Path $path;
    private FileSystemContract $fileSystem;

    public function __construct(Path $path, FileSystemContract $fileSystem)
    {
        $this->path = $path;
        $this->fileSystem = $fileSystem;
    }

    public function hasConfigFile()
    {
        return $this->fileSystem->exists($this->path->to("/includes/config.php"));
    }
}
