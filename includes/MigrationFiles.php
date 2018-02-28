<?php
namespace App;

use DirectoryIterator;

class MigrationFiles
{
    /** @var string */
    protected $migrationsPath;

    public function __construct(Application $app)
    {
        $this->migrationsPath = $app->path('install/migrations/');
    }

    public function getMigrations()
    {
        $migrations = [];
        $dir = new DirectoryIterator($this->migrationsPath);

        foreach ($dir as $fileinfo) {
            if (!preg_match("/^.+\.sql$/", $fileinfo->getFilename())) {
                continue;
            }

            $migrations[] = $fileinfo->getBasename('.sql');
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
        return $this->migrationsPath . $migration . '.sql';
    }
}