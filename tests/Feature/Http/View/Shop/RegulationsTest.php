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
        $response = $this->get('/', ['pid' => 'regulations']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Regulamin', $response->getContent());
    }
}
