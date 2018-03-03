<?php
namespace Tests\Psr4;

use App\Application;
use App\Database;
use App\License;
use App\LocaleService;
use App\Settings;
use Install\DatabaseMigration;
use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /** @var Application */
    protected $app;

    /** @var bool */
    protected $wrapInTransaction = true;

    /** @var Factory */
    protected $factory;

    protected function setUp()
    {
        if (!$this->app) {
            $this->app = $this->createApplication();
        }

        $this->factory = $this->app->make(Factory::class);
        $this->mockLicense();
        $this->mockLocale();

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var DatabaseMigration $databaseMigration */
        $databaseMigration = $this->app->make(DatabaseMigration::class);

        $db->dropAllTables();
        $databaseMigration->install('lic_000', 'abc123', 'admin', 'abc123');
        $settings->load();

        if ($this->wrapInTransaction) {
            $db->startTransaction();
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
        return require __DIR__ . '/../../bootstrap/app.php';
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

    protected function mockLocale()
    {
        $localeService = Mockery::mock(LocaleService::class);
        $localeService->shouldReceive('getLocale')->andReturn('pl');
        $this->app->instance(LocaleService::class, $localeService);
    }
}
