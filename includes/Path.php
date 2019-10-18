<?php
namespace App;

class Path
{
    /** @var string */
    protected $basePath;

    public function __construct($basePath)
    {
        $this->basePath = realpath(rtrim($basePath, '\/'));
    }

    public function to($path = '')
    {
        if (!strlen($path)) {
            return $this->basePath;
        }

        if (starts_with($path, DIRECTORY_SEPARATOR)) {
            return realpath($this->basePath . $path);
        }

        return realpath($this->basePath . DIRECTORY_SEPARATOR . $path);
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
