<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\IndexTestCase;

class RegulationsTest extends IndexTestCase
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
