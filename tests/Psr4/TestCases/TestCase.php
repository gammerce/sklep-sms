<?php
namespace Tests\Psr4\TestCases;

use App\Application;
use App\Database;
use App\License;
use App\LocaleService;
use App\Settings;
use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Psr4\Factory;

class TestCase extends BaseTestCase
{
    /** @var Application */
    protected $app;

    /** @var bool */
    protected $wrapInTransaction = true;

    /** @var Factory */
    protected $factory;

    /** @var array */
    protected $afterApplicationCreatedCallbacks = [];

    /** @var boolean */
    protected $mockLocale = true;

    protected function setUp()
    {
        if (!$this->app) {
            $this->app = $this->createApplication();
        }

        $this->factory = $this->app->make(Factory::class);
        $this->mockLicense();

        if ($this->mockLocale) {
            $this->mockLocale();
        }

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);
        $settings->load();

        /** @var Database $db */
        $db = $this->app->make(Database::class);
        if ($this->wrapInTransaction) {
            $db->startTransaction();
        }

        foreach ($this->afterApplicationCreatedCallbacks as $callback) {
            call_user_func($callback);
        }
    }

    protected function tearDown()
    {
        if ($this->app) {
            /** @var Database $db */
            $db = $this->app->make(Database::class);

            /** @var Request $request */
            $request = $this->app->make(Request::class);

            if ($this->wrapInTransaction) {
                $db->rollback();
            }

            $db->close();

            if ($request->hasSession()) {
                $request->getSession()->invalidate();
            }

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
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->singleton(Session::class, function () {
            return new Session(new MockArraySessionStorage());
        });
        return $app;
    }

    public function afterApplicationCreated(callable $callback)
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;
    }

    protected function mockLicense()
    {
        $license = Mockery::mock(License::class);
        $license->shouldReceive('validate')->andReturn();
        $license->shouldReceive('getExpires')->andReturn('');
        $license->shouldReceive('getExternalId')->andReturn(2);
        $license->shouldReceive('isForever')->andReturn(true);
        $license
            ->shouldReceive('isValid')
            ->andReturn(true)
            ->byDefault();
        $license
            ->shouldReceive('getLoadingException')
            ->andReturn(null)
            ->byDefault();
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
