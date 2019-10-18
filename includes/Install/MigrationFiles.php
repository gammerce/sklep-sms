<?php
namespace App\Install;

use App\Path;
use DirectoryIterator;

class MigrationFiles
{
    /** @var Path */
    private $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function getMigrations()
    {
        $migrations = [];
        $dir = new DirectoryIterator($this->buildPath(""));

        foreach ($dir as $fileinfo) {
            if (!preg_match("/^.+\.php$/", $fileinfo->getFilename())) {
                continue;
            }

            $migrations[] = $fileinfo->getBasename('.php');
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
