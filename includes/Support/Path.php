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

    public function to($path = ""): string
    {
        if (!strlen($path)) {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, "/");
    }
}
