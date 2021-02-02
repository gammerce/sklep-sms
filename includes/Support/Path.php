<?php
namespace App\Support;

class Path
{
    private string $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    public function to($path = ""): string
    {
        if (!strlen($path)) {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, "/");
    }
}
