<?php
namespace App\Install;

use App\Support\BasePath;
use DirectoryIterator;

class MigrationFiles
{
    private BasePath $path;

    public function __construct(BasePath $path)
    {
        $this->path = $path;
    }

    public function getMigrations()
    {
        $migrations = [];
        $dir = new DirectoryIterator($this->buildPath(""));

        foreach ($dir as $fileInfo) {
            if (str_ends_with($fileInfo->getFilename(), ".php")) {
                $migrations[] = $fileInfo->getBasename(".php");
            }
        }

        sort($migrations);

        return $migrations;
    }

    public function getLastMigration()
    {
        $migrations = $this->getMigrations();

        return end($migrations);
    }

    public function getMigrationPath($migration)
    {
        return $this->buildPath($migration . ".php");
    }

    public function buildPath($file)
    {
        return $this->path->to("migrations" . DIRECTORY_SEPARATOR . $file);
    }
}
