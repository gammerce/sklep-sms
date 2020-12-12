<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class RegulationsTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // when
        $response = $this->get("/page/regulations");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Regulamin", $response->getContent());
    }
}
