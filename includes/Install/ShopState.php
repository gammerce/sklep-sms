<?php
namespace App\Install;

use App\System\Application;
use App\System\Database;
use PDOException;

class ShopState
{
    /** @var MigrationFiles */
    private $migrationFiles;

    /** @var DatabaseMigration */
    private $databaseMigration;

    /** @var Application */
    private $app;

    /** @var RequirementsStore */
    private $requirementsStore;

    public function __construct(
        Application $application,
        MigrationFiles $migrationFiles,
        DatabaseMigration $databaseMigration,
        RequirementsStore $requirementsStore
    ) {
        $this->migrationFiles = $migrationFiles;
        $this->databaseMigration = $databaseMigration;
        $this->app = $application;
        $this->requirementsStore = $requirementsStore;
    }

    public function isUpToDate()
    {
        return $this->databaseMigration->getLastExecutedMigration() ===
            $this->migrationFiles->getLastMigration() &&
            $this->requirementsStore->areFilesInCorrectState();
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
        } catch (PDOException $e) {
            return false;
        }
    }
}
