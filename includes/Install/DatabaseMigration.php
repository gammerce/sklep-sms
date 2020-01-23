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
            ->statement("UPDATE `ss_settings` " . "SET `value`= ? WHERE `key` = 'random_key'")
            ->execute([get_random_string(16)]);

        $this->db
            ->statement(
                "UPDATE `ss_settings` " . "SET `value` = ? WHERE `key` = 'license_password';"
            )
            ->execute([$token]);

        $this->db
            ->statement(
                "INSERT INTO `ss_users` " .
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
                ->query("SELECT `name` FROM `ss_migrations` " . "ORDER BY id DESC " . "LIMIT 1")
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
        $className = $this->getClassNameFromFile($path);

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
        $this->db->statement("INSERT INTO `ss_migrations` " . "SET `name` = ?")->execute([$name]);
    }

    // https://stackoverflow.com/questions/7153000/get-class-name-from-file/44654073
    private function getClassNameFromFile($path)
    {
        $fp = fopen($path, 'r');
        $buffer = '';
        $i = 0;

        while (!feof($fp)) {
            $buffer .= fread($fp, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                if ($tokens[$i][0] === T_CLASS) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === '{') {
                            return $tokens[$i + 2][1];
                        }
                    }
                }
            }
        }

        return null;
    }
}
