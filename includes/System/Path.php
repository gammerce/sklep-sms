<?php
namespace App\System;

class Path
{
    /** @var string */
    protected $basePath;

    public function __construct($basePath)
    {
        $this->basePath = realpath($basePath);
    }

    public function to($path = '')
    {
        if (!strlen($path)) {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, "/");
    }

    public function sqlLogPath()
    {
        return $this->to('data/logs/sql.log');
    }

    public function errorsLogPath()
    {
        return $this->to('data/logs/errors.log');
    }
}
