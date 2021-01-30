<?php
namespace Tests\Psr4\TestCases;

use App\Support\Database;
use App\Support\Money;
use App\System\Application;
use App\System\License;
use App\System\Settings;
use App\Translation\LocaleService;
use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Mockery;
use MyCLabs\Enum\Enum;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Tests\Psr4\Concerns\ApplicationConcern;
use Tests\Psr4\Concerns\FileSystemConcern;
use Tests\Psr4\Concerns\MailerConcern;
use Tests\Psr4\Concerns\MockeryConcern;
use Tests\Psr4\Concerns\RequesterConcern;
use Tests\Psr4\Factory;

class TestCase extends BaseTestCase
{
    use ApplicationConcern;
    use FileSystemConcern;
    use MailerConcern;
    use MockeryConcern;
    use RequesterConcern;

    protected Application $app;
    protected bool $wrapInTransaction = true;
    protected Factory $factory;
    protected bool $mockLocale = true;
    private array $afterApplicationCreatedCallbacks = [];

    protected function setUp(): void
    {
        $this->app = $this->createApplication();
        $this->factory = $this->app->make(Factory::class);
        $this->mockLicense();
        $this->mockFileSystem();
        $this->mockMailer();
        $this->mockRequester();

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

    protected function tearDown(): void
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
        $license->shouldReceive("validate")->andReturn();
        $license->shouldReceive("getExpires")->andReturn("");
        $license->shouldReceive("getExternalId")->andReturn(2);
        $license->shouldReceive("isForever")->andReturn(true);
        $license
            ->shouldReceive("isValid")
            ->andReturn(true)
            ->byDefault();
        $license
            ->shouldReceive("getLoadingException")
            ->andReturn(null)
            ->byDefault();
        $license->shouldReceive("getFooter")->andReturn("");
        $this->app->instance(License::class, $license);
    }

    private function mockLocale()
    {
        $localeService = Mockery::mock(LocaleService::class);
        $localeService->shouldReceive("getLocale")->andReturn("pl");
        $this->app->instance(LocaleService::class, $localeService);
    }

    protected function assertAlmostSameTimestamp($expected, $value)
    {
        $this->assertLessThanOrEqual($expected + 5, $value);
        $this->assertGreaterThanOrEqual($expected - 5, $value);
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

    protected function assertSameEnum(Enum $expected, Enum $value)
    {
        $this->assertTrue($expected->equals($value), "$expected does not equal $value");
    }

    public static function assertArraySubset(
        $subset,
        $array,
        $checkForObjectIdentity = false,
        $message = ""
    ) {
        if (class_exists(Assert::class)) {
            Assert::assertArraySubset($subset, $array, $checkForObjectIdentity, $message);
        } else {
            // PHP 5.6 backward compatibility
            parent::assertArraySubset($subset, $array, $checkForObjectIdentity, $message);
        }
    }

    public static function assertStringContainsString($needle, $haystack, $message = ""): void
    {
        if (method_exists(get_parent_class(self::class), "assertStringContainsString")) {
            parent::assertStringContainsString($needle, $haystack, $message);
        } else {
            // PHP 5.6 backward compatibility
            parent::assertContains($needle, $haystack, $message);
        }
    }

    public static function assertMatchesRegularExpression($pattern, $string, $message = ""): void
    {
        if (method_exists(get_parent_class(self::class), "assertMatchesRegularExpression")) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            // PHP 5.6 backward compatibility
            parent::assertRegExp($pattern, $string, $message);
        }
    }

    public static function assertIsString($actual, $message = ""): void
    {
        if (method_exists(get_parent_class(self::class), "assertIsString")) {
            parent::assertIsString($actual);
        } else {
            // PHP 5.6 backward compatibility
            parent::assertInternalType("string", $actual);
        }
    }

    /**
     * @param Money|int|null $expected
     * @param Money|int|null $value
     */
    protected function assertEqualsMoney($expected, $value)
    {
        $this->assertEquals(as_money($expected), as_money($value));
    }

    private function databaseHas($table, array $data)
    {
        if (!$data) {
            return false;
        }

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        [$params, $values] = map_to_params($data);
        $params = implode(" AND ", $params);

        $statement = $db->statement("SELECT 1 FROM `{$table}` WHERE {$params}");
        $statement->execute($values);

        return $statement->rowCount() > 0;
    }
}
