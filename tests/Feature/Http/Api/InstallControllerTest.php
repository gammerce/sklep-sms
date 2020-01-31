<?php
namespace Tests\Feature\Http\Api;

use App\Install\ShopState;
use App\System\Application;
use App\Support\Database;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Psr4\Concerns\ApplicationConcern;
use Tests\Psr4\Concerns\FileSystemConcern;
use Tests\Psr4\Concerns\MakesHttpRequests;

class InstallControllerTest extends TestCase
{
    use ApplicationConcern;
    use FileSystemConcern;
    use MakesHttpRequests;

    /** @var Application */
    private $app;

    /** @var Database */
    private $db;

    private $dbName = "install_test";

    protected function setUp()
    {
        $this->app = $this->createApplication();
        $this->db = new Database(
            getenv('DB_HOST'),
            getenv('DB_PORT') ?: 3306,
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD'),
            getenv('DB_DATABASE')
        );
        $this->mockFileSystem();
        $this->mockShopState();

        $this->db->createDatabaseIfNotExists($this->dbName);
    }

    protected function tearDown()
    {
        $this->db->dropDatabaseIfExists($this->dbName);
        $this->db->close();
        $this->tearDownApplication($this->app);
    }

    /** @test */
    public function perform_installation()
    {
        // when
        $response = $this->post("/api/install", [
            "db_host" => getenv('DB_HOST'),
            "db_port" => getenv('DB_PORT') ?: 3306,
            "db_user" => getenv('DB_USERNAME'),
            "db_password" => getenv('DB_PASSWORD'),
            "db_db" => $this->dbName,
            "license_token" => "abc123",
            "admin_username" => "root",
            "admin_password" => "secret",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $this->assertSame("Instalacja przebiegła pomyślnie.", $json["text"]);
    }

    /** @test */
    public function fails_when_invalid_database_credentials_are_given()
    {
        // when
        $response = $this->post("/api/install", [
            "db_host" => getenv('DB_HOST'),
            "db_port" => getenv('DB_PORT') ?: 3306,
            "db_user" => getenv('DB_USERNAME'),
            "db_password" => "blahblah",
            "db_db" => $this->dbName,
            "license_token" => "abc123",
            "admin_username" => "root",
            "admin_password" => "secret",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith(
            "SQLSTATE[HY000] [1045] Access denied",
            $response->getContent()
        );
    }

    /** @test */
    public function fails_when_invalid_not_enough_data_is_given()
    {
        // when
        $response = $this->post("/api/install", [
            "db_host" => getenv('DB_HOST'),
            "db_port" => getenv('DB_PORT') ?: 3306,
            "db_user" => getenv('DB_USERNAME'),
            "db_password" => getenv('DB_PASSWORD'),
            "db_db" => $this->dbName,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
    }

    /** @test */
    public function is_not_performed_if_shop_has_been_already_installed()
    {
        // given
        $shopState = $this->app->make(ShopState::class);
        $shopState->shouldReceive("isUpToDate")->andReturn(true);
        $shopState->shouldReceive("isInstalled")->andReturn(true);

        // when
        $response = $this->post("/api/install", [
            "db_host" => getenv('DB_HOST'),
            "db_port" => getenv('DB_PORT') ?: 3306,
            "db_user" => getenv('DB_USERNAME'),
            "db_password" => getenv('DB_PASSWORD'),
            "db_db" => $this->dbName,
            "license_token" => "abc123",
            "admin_username" => "root",
            "admin_password" => "secret",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("Shop has been installed already.", $response->getContent());
    }

    private function mockShopState()
    {
        $shopState = Mockery::mock(ShopState::class);
        $shopState
            ->shouldReceive("isUpToDate")
            ->andReturn(false)
            ->byDefault();
        $shopState
            ->shouldReceive("isInstalled")
            ->andReturn(false)
            ->byDefault();
        $shopState
            ->shouldReceive("requiresAction")
            ->andReturn(false)
            ->byDefault();
        $this->app->instance(ShopState::class, $shopState);
    }
}
