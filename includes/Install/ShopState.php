<?php
namespace App\Install;

use App\Support\Database;
use PDOException;

class ShopState
{
    private MigrationFiles $migrationFiles;
    private DatabaseMigration $databaseMigration;
    private RequirementsStore $requirementsStore;
    private Database $db;

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

    public function requiresAction()
    {
        return !$this->isInstalled() || !$this->isUpToDate();
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
