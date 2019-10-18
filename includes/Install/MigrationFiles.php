<?php
namespace App\Install;

use App\Path;
use DirectoryIterator;

class MigrationFiles
{
    /** @var string */
    protected $migrationsPath;

    public function __construct(Path $path)
    {
        $this->migrationsPath = $path->to('migrations/');
    }

    public function getMigrations()
    {
        $migrations = [];
        $dir = new DirectoryIterator($this->migrationsPath);

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
        return $this->migrationsPath . $migration . '.php';
    }

    public function path($file)
    {
        return $this->migrationsPath . $file;
    }
}
