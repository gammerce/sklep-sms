<?php
namespace App\Install;

use App\System\Application;
use App\System\Database;
use PDOException;

class DatabaseMigration
{
    /** @var Application */
    private $app;

    /** @var Database */
    private $db;

    /** @var MigrationFiles */
    private $migrationFiles;

    public function __construct(Application $app, Database $db, MigrationFiles $migrationFiles)
    {
        $this->app = $app;
        $this->db = $db;
        $this->migrationFiles = $migrationFiles;
    }

    public function setup($token, $adminUsername, $adminPassword)
    {
        foreach ($this->migrationFiles->getMigrations() as $migration) {
            $this->migrate($migration);
        }

        $salt = get_random_string(8);

        $this->db
            ->statement(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "settings` " .
                    "SET `value`= ? WHERE `key` = 'random_key'"
            )
            ->execute([get_random_string(16)]);

        $this->db
            ->statement(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "settings` " .
                    "SET `value` = ? WHERE `key` = 'license_password';"
            )
            ->execute([$token]);

        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `username` = ?, `password` = ?, `salt` = ?, `regip` = ?, `groups` = '2', `regdate` = NOW();"
            )
            ->execute([$adminUsername, hash_password($adminPassword, $salt), $salt, get_ip()]);
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
            return $this->db
                ->query(
                    "SELECT `name` FROM `" .
                        TABLE_PREFIX .
                        "migrations` " .
                        "ORDER BY id DESC " .
                        "LIMIT 1"
                )
                ->fetchColumn();
        } catch (PDOException $e) {
            if (preg_match("/Table .*ss_migrations.* doesn't exist/", $e->getMessage())) {
                // It means that user has installed shop sms using old codebase,
                // that is why we want to create migration table for him and also
                // fake init migration so as not to overwrite his database
                $this->migrate('2018_01_14_224424_create_migrations');
                $this->saveExecutedMigration('2018_01_14_230340_init');

                return $this->getLastExecutedMigration();
            }

            throw $e;
        }
    }

    private function migrate($migration)
    {
        $path = $this->migrationFiles->getMigrationPath($migration);
        $this->executeMigration($path);
        $this->saveExecutedMigration($migration);
    }

    private function executeMigration($path)
    {
        $classes = get_declared_classes();
        include $path;
        $diff = array_diff(get_declared_classes(), $classes);

        foreach ($diff as $class) {
            if (is_subclass_of($class, Migration::class)) {
                /** @var Migration $migrationObject */
                $migrationObject = $this->app->make($class);
                $migrationObject->up();
            }
        }
    }

    private function saveExecutedMigration($name)
    {
        $this->db
            ->statement("INSERT INTO `" . TABLE_PREFIX . "migrations` " . "SET `name` = ?")
            ->execute([$name]);
    }
}
