<?php
namespace Tests\Psr4\TestCases;

use App\Support\Database;
use App\System\Application;
use App\System\License;
use App\System\Settings;
use App\Translation\LocaleService;
use Mockery;
use MyCLabs\Enum\Enum;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Tests\Psr4\Concerns\ApplicationConcern;
use Tests\Psr4\Concerns\FileSystemConcern;
use Tests\Psr4\Concerns\MailerConcern;
use Tests\Psr4\Concerns\MockeryConcern;
use Tests\Psr4\Factory;

class TestCase extends BaseTestCase
{
    use ApplicationConcern;
    use FileSystemConcern;
    use MailerConcern;
    use MockeryConcern;

    /** @var Application */
    protected $app;

    /** @var bool */
    protected $wrapInTransaction = true;

    /** @var Factory */
    protected $factory;

    /** @var boolean */
    protected $mockLocale = true;

    /** @var array */
    private $afterApplicationCreatedCallbacks = [];

    protected function setUp()
    {
        if (!$this->app) {
            $this->app = $this->createApplication();
        }

        $this->factory = $this->app->make(Factory::class);
        $this->mockLicense();
        $this->mockFileSystem();
        $this->mockMailer();

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

            $this->tearDownApplication($this->app);
        }

        $this->closeMockery();
    }

    protected function afterApplicationCreated(callable $callback)
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;
    }

    private function mockLicense()
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

    private function mockLocale()
    {
        $localeService = Mockery::mock(LocaleService::class);
        $localeService->shouldReceive('getLocale')->andReturn('pl');
        $this->app->instance(LocaleService::class, $localeService);
    }

    protected function assertAlmostSameTimestamp($expected, $value)
    {
        $this->assertLessThanOrEqual($expected + 5, $value);
        $this->assertGreaterThanOrEqual($expected - 5, $value);
    }

    protected function assertDatabaseHas($table, array $data)
    {
        $this->assertTrue($this->databaseHas($table, $data));
    }

    protected function assertSameEnum(Enum $expected, Enum $value)
    {
        $this->assertTrue($expected->equals($value), "$expected does not equal $value");
    }

    private function databaseHas($table, array $data)
    {
        if (!$data) {
            return false;
        }

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        $params = map_to_where_params($data);
        $values = map_to_values($data);

        $statement = $db->statement("SELECT `id` FROM `{$table}` WHERE {$params}");
        $statement->execute($values);

        return $statement->rowCount() > 0;
    }
}
