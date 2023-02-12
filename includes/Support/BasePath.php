<?php
namespace App\Support;

class BasePath
{
    private string $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    public static function temporary(): BasePath
    {
        return new BasePath(sys_get_temp_dir());
    }

    public function to(string $subpath): string
    {
        if (!strlen($subpath)) {
            return $this->basePath;
        }
        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($subpath, "/");
    }
}
