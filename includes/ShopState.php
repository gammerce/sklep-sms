<?php
namespace App;

use App\Exceptions\SqlQueryException;
use Install\DatabaseMigration;
use InvalidArgumentException;

class ShopState
{
    /** @var MigrationFiles */
    protected $migrationFiles;

    /** @var DatabaseMigration */
    protected $databaseMigration;

    public function __construct(MigrationFiles $migrationFiles, DatabaseMigration $databaseMigration)
    {
        $this->migrationFiles = $migrationFiles;
        $this->databaseMigration = $databaseMigration;
    }

    public function isUpToDate()
    {
        return $this->databaseMigration->getLastExecutedMigration() === $this->migrationFiles->getLastMigration();
    }

    public function getFileVersion()
    {
        return self::versionToInteger(VERSION);
    }

    public function getMigrationFileVersion()
    {
        $migrations = $this->migrationFiles->getMigrations();

        end($migrations);

        return key($migrations);
    }

    public static function versionToInteger($version)
    {
        $exploded = explode('.', $version);

        if (count($exploded) !== 3) {
            throw new InvalidArgumentException('Invalid version');
        }

        return $exploded[0] * 10000 + $exploded[1] * 100 + $exploded[2];
    }

    public static function isInstalled()
    {
        /** @var Database $db */
        $db = app()->make(Database::class);

        if ($db->isConnected()) {
            return true;
        }

        try {
            $db->connect();
            return true;
        } catch (SqlQueryException $e) {
            return false;
        }
    }
}
