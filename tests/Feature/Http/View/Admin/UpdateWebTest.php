<?php
namespace Tests\Feature\Http\View\Admin;

use App\Requesting\Response as RequestingResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class UpdateWebTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs([
                "https://api.github.com/repos/gammerce/sklep-sms/releases/latest",
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
        $response = $this->get("/admin/update_web");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString(
            "Skrypt sklepu jest zaktualizowany do najnowszej wersji",
            $response->getContent()
        );
    }

    /** @test */
    public function it_shows_shop_should_be_upgraded()
    {
        // given
        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs([
                "https://api.github.com/repos/gammerce/sklep-sms/releases/latest",
                [],
                [],
                4,
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "tag_name" => "5.0.0",
                    ])
                )
            );

        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/admin/update_web");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString(
            "<div class=\"title is-4\">Aktualizacja strony WWW",
            $response->getContent()
        );
    }
}
