<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class HomepageTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // when
        $response = $this->get("/");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Strona główna", $response->getContent());
    }

    /** @test */
    public function not_found()
    {
        // when
        $response = $this->get("/page/aqw");

        // then
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertStringContainsString("Strona nie została znaleziona", $response->getContent());
    }
}
