<?php
namespace App\Install;

use App\Support\Database;
use PDOException;

class ShopState
{
    private MigrationFiles $migrationFiles;
    private DatabaseMigration $databaseMigration;
    private RequirementStore $requirementStore;
    private Database $db;

    public function __construct(
        MigrationFiles $migrationFiles,
        DatabaseMigration $databaseMigration,
        RequirementStore $requirementStore,
        Database $db
    ) {
        $this->migrationFiles = $migrationFiles;
        $this->databaseMigration = $databaseMigration;
        $this->requirementStore = $requirementStore;
        $this->db = $db;
    }

    public function requiresAction(): bool
    {
        return !$this->isInstalled() || !$this->isUpToDate();
    }

    public function isUpToDate(): bool
    {
        return $this->databaseMigration->getLastExecutedMigration() ===
            $this->migrationFiles->getLastMigration() &&
            $this->requirementStore->areFilesInCorrectState();
    }

    public function isInstalled(): bool
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
