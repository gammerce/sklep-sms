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

    public function run($dbName, $licenseToken, $adminUsername, $adminEmail, $adminPassword): void
    {
        $db = new Database(
            getenv("DB_HOST"),
            getenv("DB_PORT") ?: 3306,
            getenv("DB_USERNAME"),
            getenv("DB_PASSWORD"),
            $dbName
        );
        $db->connect();
        $this->app->instance(Database::class, $db);

        /** @var DatabaseMigration $migrator */
        $migrator = $this->app->make(DatabaseMigration::class);

        $appliedMigrations = $migrator->update();

        // Do not seed initial data if there were already some tables in the database
        if (in_array("2018_01_14_224424_create_migrations", $appliedMigrations, true)) {
            $migrator->seedInitialData(
                $licenseToken,
                $adminUsername,
                $adminPassword,
                $adminEmail,
                ""
            );
        }
    }
}
