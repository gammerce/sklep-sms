<?php
namespace App\Install;

use App\System\Database;
use PDOException;

class ShopState
{
    /** @var MigrationFiles */
    private $migrationFiles;

    /** @var DatabaseMigration */
    private $databaseMigration;

    /** @var RequirementsStore */
    private $requirementsStore;

    /** @var Database */
    private $db;

    public function __construct(
        MigrationFiles $migrationFiles,
        DatabaseMigration $databaseMigration,
        RequirementsStore $requirementsStore,
        Database $db
    ) {
        $this->migrationFiles = $migrationFiles;
        $this->databaseMigration = $databaseMigration;
        $this->requirementsStore = $requirementsStore;
        $this->db = $db;
    }

    public function isUpToDate()
    {
        return $this->databaseMigration->getLastExecutedMigration() ===
            $this->migrationFiles->getLastMigration() &&
            $this->requirementsStore->areFilesInCorrectState();
    }

    public function isInstalled()
    {
        if ($this->db->isConnected()) {
            return true;
        }

        try {
            $this->db->connect();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
