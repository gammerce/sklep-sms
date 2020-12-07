<?php
namespace Tests\Feature\Http\View\Admin;

use App\Exceptions\LicenseRequestException;
use App\Requesting\Response as RequestingResponse;
use App\System\License;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class HomepageTest extends HttpTestCase
{
    use MakePurchaseConcern;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRandomExtraFlagsPurchase();

        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs([
                "https://api.github.com/repos/gammerce/sklep-sms/releases/latest",
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "tag_name" => "3.10.0",
                    ])
                )
            );

        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs([
                "https://api.github.com/repos/gammerce/plugin-amxmodx/releases/latest",
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "tag_name" => "3.10.0",
                    ])
                )
            );

        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs([
                "https://api.github.com/repos/gammerce/plugin-sourcemod/releases/latest",
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "tag_name" => "3.10.0",
                    ])
                )
            );
    }

    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/admin");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString("<div class=\"title is-4\">Strona główna", $response->getContent());
    }

    /** @test */
    public function user_can_access_acp_if_license_is_invalid()
    {
        // given
        $license = $this->app->make(License::class);
        $license->shouldReceive("isValid")->andReturn(false);
        $license->shouldReceive("getLoadingException")->andReturn(new LicenseRequestException());

        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/admin");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
    }

    /** @test */
    public function requires_login_when_not_logged()
    {
        // when
        $response = $this->get("/admin");

        // then
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith("/admin/login", $response->headers->get("Location"));
    }
}
