<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Models\Server;
use Tests\Psr4\TestCases\HttpTestCase;

class ServiceActionControllerTest extends HttpTestCase
{
    /** @var Server */
    private $server;

    protected function setUp()
    {
        parent::setUp();

        $this->server = $this->factory->server();
        $this->factory->serverService([
            "service_id" => "vippro",
            "server_id" => $this->server->getId(),
        ]);
        $this->factory->price([
            "service_id" => "vippro",
        ]);
    }

    /** @test */
    public function action_prices_for_server()
    {
        // when
        $response = $this->post("/api/services/vippro/actions/prices_for_server", [
            "server_id" => $this->server->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("<option value=\"\">Wybierz ilość</option>", $response->getContent());
    }

    /** @test */
    public function action_servers_for_service()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/services/vippro/actions/servers_for_service", [
            "server_id" => $this->server->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("</option>", $response->getContent());
    }
}
