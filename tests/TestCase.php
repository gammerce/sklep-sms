<?php
namespace Tests;

use App\Application;
use App\Database;
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

        if ($this->wrapInTransaction) {
            /** @var Database $db */
            $db = $this->app->make(Database::class);
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
}
