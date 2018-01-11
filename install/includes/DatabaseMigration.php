<?php

require_once SCRIPT_ROOT . "includes/ShopState.php";
require_once SCRIPT_ROOT . "includes/MigrationFiles.php";
require_once SCRIPT_ROOT . "install/includes/InstallManager.php";

class DatabaseMigration
{
    /** @var Database */
    protected $db;

    /** @var Translator */
    protected $lang;

    /** @var MigrationFiles */
    protected $migrationFiles;

    /** @var ShopState */
    protected $shopState;

    /** @var InstallManager */
    protected $installManager;

    public function __construct(Database $db, Translator $translator)
    {
        $this->db = $db;
        $this->lang = $translator;
        $this->shopState = new ShopState($db);
        $this->migrationFiles = new MigrationFiles();
        $this->installManager = InstallManager::instance();
    }

    public function install($licenseId, $licensePassword, $adminUsername, $adminPassword)
    {
        $queries = $this->splitSQL($this->migrationsPath . 'init.sql');

        $salt = get_random_string(8);
        $customizedQueried = [
            $this->db->prepare(
                "UPDATE `" . TABLE_PREFIX . "settings` " .
                "SET `value`='%s' WHERE `key`='random_key';",
                [get_random_string(16)]
            ),
            $this->db->prepare(
                "UPDATE `" . TABLE_PREFIX . "settings` " .
                "SET `value`='%s' WHERE `key`='license_login';",
                [$licenseId]
            ),
            $this->db->prepare(
                "UPDATE `" . TABLE_PREFIX . "settings` " .
                "SET `value`='%s' WHERE `key`='license_password';",
                [md5($licensePassword)]
            ),
            $this->db->prepare(
                "INSERT INTO `" . TABLE_PREFIX . "users` " .
                "SET `username` = '%s', `password` = '%s', `salt` = '%s', `regip` = '%s', `groups` = '2';",
                [$adminUsername, hash_password($adminPassword, $salt), $salt, get_ip()]
            ),
        ];

        $this->executeQueries(array_merge($queries, $customizedQueried));

        $this->update();
    }

    public function update()
    {
        $dbVersion = $this->shopState->getDbVersion();
        $migrationFileVersion = $this->shopState->getMigrationFileVersion();
        $migrationPaths = $this->migrationFiles->getMigrationPaths();

        foreach ($migrationPaths as $version => $path) {
            if ($dbVersion < $version && $version <= $migrationFileVersion) {
                $this->migrate($path, $version);
                $dbVersion = $version;
            }
        }
    }

    protected function migrate($path, $version)
    {
        $queries = $this->splitSQL($path);
        $this->executeQueries($queries);
        $this->bumpVersion($version);
    }

    protected function executeQueries($queries)
    {
        foreach ($queries as $query) {
            if (!strlen($query)) {
                continue;
            }

            try {
                $this->db->query($query);
            } catch (SqlQueryException $e) {
                $this->installManager->handleSqlException($e);
            }
        }
    }

    protected function bumpVersion($version)
    {
        $this->db->query($this->db->prepare(
            "INSERT INTO `" . TABLE_PREFIX . "migrations` " .
            "SET `version` = '%s'",
            [$version]
        ));
    }

    protected function splitSQL($path, $delimiter = ';')
    {
        $queries = [];

        $path = fopen($path, 'r');

        if (is_resource($path) === true) {
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

            return $queries;
        }

        throw new InvalidArgumentException('Invalid path to queries');
    }
}