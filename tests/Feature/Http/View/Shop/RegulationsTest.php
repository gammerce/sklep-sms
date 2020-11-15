<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class RegulationsTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given

        // when
        $response = $this->get("/page/regulations");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("Regulamin", $response->getContent());
    }
}
