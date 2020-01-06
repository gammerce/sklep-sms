<?php
namespace Tests\Feature\Http\Api;

use App\Install\EnvCreator;
use App\Install\ShopState;
use App\System\Application;
use App\System\Database;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Psr4\Concerns\ApplicationConcern;
use Tests\Psr4\Concerns\MakesHttpRequests;
use Tests\Psr4\Concerns\SetupManagerConcern;

class InstallControllerTest extends TestCase
{
    use ApplicationConcern;
    use MakesHttpRequests;
    use SetupManagerConcern;

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
        $this->mockEnvCreator();
        $this->mockSetupManager();

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
        // given
        $this->mockShopState();

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
        // given
        $this->mockShopState();

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
        // given
        $this->mockShopState();

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
        $shopState->shouldReceive("isUpToDate")->andReturn(false);
        $shopState->shouldReceive("isInstalled")->andReturn(false);
        $this->app->instance(ShopState::class, $shopState);
    }

    private function mockEnvCreator()
    {
        $envCreator = Mockery::mock(EnvCreator::class);
        $envCreator->shouldReceive("create")->andReturnNull();
        $this->app->instance(EnvCreator::class, $envCreator);
    }
}
