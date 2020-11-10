<?php
namespace App\Install;

use App\Support\Database;
use App\System\Application;
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

    /**
     * @param string $token
     * @param string $adminUsername
     * @param string $adminPassword
     * @param string $ip
     */
    public function setup($token, $adminUsername, $adminPassword, $ip)
    {
        foreach ($this->migrationFiles->getMigrations() as $migration) {
            $this->migrate($migration);
        }

        $salt = get_random_string(8);

        $this->db
            ->statement("UPDATE `ss_settings` SET `value`= ? WHERE `key` = 'random_key'")
            ->execute([get_random_string(16)]);

        $this->db
            ->statement("UPDATE `ss_settings` SET `value` = ? WHERE `key` = 'license_token'")
            ->execute([$token]);

        $this->db
            ->statement(
                "INSERT INTO `ss_users` " .
                    "SET `username` = ?, `password` = ?, `salt` = ?, `regip` = ?, `groups` = '2', `regdate` = NOW();"
            )
            ->execute([$adminUsername, hash_password($adminPassword, $salt), $salt, $ip]);
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
                ->query("SELECT `name` FROM `ss_migrations` ORDER BY id DESC LIMIT 1")
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
        $className = get_class_from_file($path);

        if ($className) {
            require_once $path;

            if (is_subclass_of($className, Migration::class)) {
                /** @var Migration $migrationObject */
                $migrationObject = $this->app->make($className);
                $migrationObject->up();
            }
        }
    }

    private function saveExecutedMigration($name)
    {
        $this->db->statement("INSERT INTO `ss_migrations` SET `name` = ?")->execute([$name]);
    }
}
