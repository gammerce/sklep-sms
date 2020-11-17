<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ProfileTest extends HttpTestCase
{
    /** @test */
    public function is_loads()
    {
        // given
        $this->actingAs(
            $this->factory->user([
                "username" => "my_example_username",
            ])
        );

        // when
        $response = $this->get("/page/profile");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Profil", $response->getContent());
        $this->assertContains("my_example_username", $response->getContent());
    }

    /** @test */
    public function requires_being_logged()
    {
        // when
        $response = $this->get("/page/profile");

        // then
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith("/login", $response->headers->get("Location"));
    }
}
