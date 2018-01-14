<?php
namespace App;

use DirectoryIterator;

class MigrationFiles
{
    /** @var string */
    protected $migrationsPath;

    public function __construct()
    {
        $this->migrationsPath = SCRIPT_ROOT . '/install/migrations/';
    }

    public function getMigrationPaths()
    {
        $paths = [];
        $dir = new DirectoryIterator($this->migrationsPath);

        foreach ($dir as $fileinfo) {
            if (!preg_match("/[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}\.sql/", $fileinfo->getFilename())) {
                continue;
            }

            $version = substr($fileinfo->getFilename(), 0, -4);
            $versionNumber = ShopState::versionToInteger($version);
            $paths[$versionNumber] = $fileinfo->getRealPath();
        }

        ksort($paths);

        return $paths;
    }
}