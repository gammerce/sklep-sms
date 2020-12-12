<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\ServerRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class ServerTokenControllerTest extends HttpTestCase
{
    /** @var ServerRepository */
    private $serverRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverRepository = $this->app->make(ServerRepository::class);
    }

    /** @test */
    public function regenerates_server_token()
    {
        // given
        $this->actingAs($this->factory->admin());
        $server = $this->factory->server();

        // when
        $response = $this->post("/api/admin/servers/{$server->getId()}/token");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshServer = $this->serverRepository->get($server->getId());
        $this->assertSame($json["data"]["token"], $freshServer->getToken());
    }
}
