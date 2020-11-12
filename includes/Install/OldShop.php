<?php
namespace App\Install;

use App\Support\FileSystemContract;
use App\Support\Path;

class OldShop
{
    /** @var Path */
    private $path;

    /** @var FileSystemContract */
    private $fileSystem;

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
