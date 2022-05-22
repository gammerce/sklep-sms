<?php

namespace Tests\Psr4\TestCases;

use App\Support\Database;
use App\System\Settings;

class TestCase extends UnitTestCase
{
    protected bool $wrapInTransaction = true;

    protected function setUp(): void
    {
        $this->afterSetUp(fn() => $this->setUpDatabase());
        $this->beforeTearDown(fn() => $this->tearDownDatabase());

        parent::setUp();
    }

    private function setUpDatabase(): void
    {
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);
        $settings->load();

        /** @var Database $db */
        $db = $this->app->make(Database::class);
        if ($this->wrapInTransaction) {
            $db->startTransaction();
        }
    }

    private function tearDownDatabase(): void
    {
        /** @var Database $db */
        $db = $this->app->make(Database::class);

        if ($this->wrapInTransaction) {
            $db->rollback();
        }

        $db->close();
    }

    protected function assertDatabaseHas($table, array $data)
    {
        $this->assertTrue(
            $this->databaseHas($table, $data),
            "Database does not contain given data."
        );
    }

    protected function assertDatabaseDoesntHave($table, array $data)
    {
        $this->assertFalse($this->databaseHas($table, $data), "Database does contain given data.");
    }

    private function databaseHas($table, array $data): bool
    {
        if (!$data) {
            return false;
        }

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        [$params, $values] = map_to_params($data, true);
        $params = implode(" AND ", $params);

        $statement = $db->statement("SELECT 1 FROM `{$table}` WHERE {$params}");
        $statement->bindAndExecute($values);

        return $statement->rowCount() > 0;
    }
}
