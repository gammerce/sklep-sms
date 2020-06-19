<?php
namespace App\Support;

class Path
{
    /** @var string */
    private $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    public function to($path = "")
    {
        if (!strlen($path)) {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, "/");
    }
}
