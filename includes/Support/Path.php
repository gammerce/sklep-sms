<?php
namespace App\Support;

class Path
{
    private string $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    public static function temporary(): Path
    {
        return new Path(sys_get_temp_dir());
    }

    public function to(string $subpath): string
    {
        if (!strlen($subpath)) {
            return $this->basePath;
        }
        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($subpath, "/");
    }
}
