<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class LoginTest extends HttpTestCase
{
    /** @test */
    public function is_loads()
    {
        // when
        $response = $this->get("/page/login");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Zaloguj się", $response->getContent());
    }

    /** @test */
    public function requires_being_not_authorized()
    {
        // given
        $this->actingAs($this->factory->user());

        // when
        $response = $this->get("/page/login");

        // then
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith("/", $response->headers->get("Location"));
    }
}
