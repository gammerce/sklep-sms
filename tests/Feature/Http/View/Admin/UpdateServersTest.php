<?php
namespace Tests\Feature\Http\View\Admin;

use App\Requesting\Response as RequestingResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class UpdateServersTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->factory->server();
        $this->factory->server();

        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs([
                "https://api.github.com/repos/gammerce/plugin-amxmodx/releases/latest",
                [],
                [],
                4,
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "tag_name" => "3.0.0",
                    ])
                )
            );
        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs([
                "https://api.github.com/repos/gammerce/plugin-sourcemod/releases/latest",
                [],
                [],
                4,
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "tag_name" => "3.0.0",
                    ])
                )
            );

        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/admin/update_servers");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString(
            "Skrypt sklepu jest zaktualizowany do najnowszej wersji",
            $response->getContent()
        );
    }
}
