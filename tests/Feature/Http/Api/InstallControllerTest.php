<?php
namespace Tests\Feature\Http\Api;

use App\Install\ShopState;
use App\System\Application;
use App\System\Database;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Psr4\Concerns\ApplicationConcern;
use Tests\Psr4\Concerns\MakesHttpRequests;

class InstallControllerTest extends TestCase
{
    use ApplicationConcern;
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

    private function mockShopState()
    {
        $shopStateMock = Mockery::mock(ShopState::class);
        $shopStateMock->shouldReceive("isUpToDate")->andReturn(false);
        $shopStateMock->shouldReceive("isInstalled")->andReturn(false);
        $this->app->instance(ShopState::class, $shopStateMock);
    }
}
