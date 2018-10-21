<?php
namespace Install;

use App\Database;
use App\Exceptions\SqlQueryException;
use App\MigrationFiles;
use InvalidArgumentException;

class DatabaseMigration
{
    /** @var Database */
    protected $db;

    /** @var MigrationFiles */
    protected $migrationFiles;

    public function __construct(Database $db, MigrationFiles $migrationFiles)
    {
        $this->db = $db;
        $this->migrationFiles = $migrationFiles;
    }

    public function install($token, $adminUsername, $adminPassword)
    {
        foreach ($this->migrationFiles->getMigrations() as $migration) {
            $this->migrate($migration);
        }

        $salt = get_random_string(8);
        $queries = [
            $this->db->prepare(
                "UPDATE `" . TABLE_PREFIX . "settings` " .
                "SET `value`='%s' WHERE `key`='random_key';",
                [get_random_string(16)]
            ),
            $this->db->prepare(
                "UPDATE `" . TABLE_PREFIX . "settings` " .
                "SET `value`='%s' WHERE `key`='license_password';",
                [md5($token)]
            ),
            $this->db->prepare(
                "INSERT INTO `" . TABLE_PREFIX . "users` " .
                "SET `username` = '%s', `password` = '%s', `salt` = '%s', `regip` = '%s', `groups` = '2';",
                [$adminUsername, hash_password($adminPassword, $salt), $salt, get_ip()]
            ),
        ];

        $this->executeQueries($queries);
    }

    public function update()
    {
        $lastExecutedMigration = $this->getLastExecutedMigration();
        $migrations = $this->migrationFiles->getMigrations();

        foreach ($migrations as $migration) {
            if ($lastExecutedMigration < $migration) {
                $this->migrate($migration);
                $lastExecutedMigration = $migration;
            }
        }
    }

    public function getLastExecutedMigration()
    {
        try {
            return $this->db->get_column(
                "SELECT `name` FROM `" . TABLE_PREFIX . "migrations` " .
                "ORDER BY id DESC " .
                "LIMIT 1",
                'name'
            );
        } catch (SqlQueryException $e) {
            if (preg_match("/Table .*ss_migrations.* doesn't exist/", $e->getError())) {
                // It means that user has installed shop sms but using old codebase,
                // that is why we want to create migration table for him and also
                // fake init migration so as not to overwrite his database
                $this->migrate('2018_01_14_224424_create_migrations');
                $this->saveExecutedMigration('2018_01_14_230340_init');

                return $this->getLastExecutedMigration();
            }

            throw $e;
        }
    }

    protected function migrate($migration)
    {
        $path = $this->migrationFiles->getMigrationPath($migration);
        $queries = $this->splitSQLFile($path);
        $this->executeQueries($queries);
        $this->saveExecutedMigration($migration);
    }

    protected function executeQueries($queries)
    {
        foreach ($queries as $query) {
            $this->db->query($query);
        }
    }

    protected function saveExecutedMigration($name)
    {
        $this->db->query($this->db->prepare(
            "INSERT INTO `" . TABLE_PREFIX . "migrations` " .
            "SET `name` = '%s'",
            [$name]
        ));
    }

    protected function splitSQLFile($path, $delimiter = ';')
    {
        $queries = [];

        $path = fopen($path, 'r');

        if (is_resource($path) !== true) {
            throw new InvalidArgumentException('Invalid path to queries');
        }

        $query = [];

        while (feof($path) === false) {
            $query[] = fgets($path);

            if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
                $query = trim(implode('', $query));
                $queries[] = $query;
            }

            if (is_string($query) === true) {
                $query = [];
            }
        }

        fclose($path);

        return array_filter($queries, function ($query) {
            return strlen($query);
        });
    }
}