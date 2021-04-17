<?php
namespace App\Install;

use App\Support\Database;
use App\System\Application;

class MigrateCommand
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function run(
        $dbHost,
        $dbPort,
        $dbUser,
        $dbPassword,
        $dbName,
        $licenseToken,
        $adminUsername,
        $adminEmail,
        $adminPassword
    ): void {
        $db = new Database($dbHost, $dbPort, $dbUser, $dbPassword, $dbName);
        $db->connect();
        $this->app->instance(Database::class, $db);

        /** @var DatabaseMigration $migrator */
        $migrator = $this->app->make(DatabaseMigration::class);

        $migrator->setup($licenseToken, $adminUsername, $adminPassword, $adminEmail);
    }
}
