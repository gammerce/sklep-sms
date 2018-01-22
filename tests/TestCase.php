<?php
namespace Tests;

use App\Application;
use App\Database;
use App\License;
use Install\DatabaseMigration;
use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /** @var Application */
    protected $app;

    /** @var bool */
    protected $wrapInTransaction = true;

    protected function setUp()
    {
        if (!$this->app) {
            $this->app = $this->createApplication();
        }

        $this->mockLicense();

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        /** @var DatabaseMigration $databaseMigration */
        $databaseMigration = $this->app->make(DatabaseMigration::class);

        $db->dropAllTables();
        $databaseMigration->install('lic_000', 'abc123', 'admin', 'abc123');

        if ($this->wrapInTransaction) {
            $db->start_transaction();
        }
    }

    protected function tearDown()
    {
        if ($this->app) {
            /** @var Database $db */
            $db = $this->app->make(Database::class);

            if ($this->wrapInTransaction) {
                $db->rollback();
            }

            $db->close();

            $this->app->flush();
            $this->app = null;
        }

        if (class_exists('Mockery')) {
            if ($container = Mockery::getContainer()) {
                $this->addToAssertionCount($container->mockery_getExpectationCount());
            }

            Mockery::close();
        }
    }

    protected function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    protected function mockLicense()
    {
        $license = Mockery::mock(License::class);
        $license->shouldReceive('validate')->andReturn();
        $license->shouldReceive('getPage')->andReturn('');
        $license->shouldReceive('getExpires')->andReturn('');
        $license->shouldReceive('isForever')->andReturn(true);
        $license->shouldReceive('isValid')->andReturn(true);
        $license->shouldReceive('getFooter')->andReturn('');
        $this->app->instance(License::class, $license);
    }
}
