<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class HomepageTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given

        // when
        $response = $this->get('/');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Strona główna', $response->getContent());
    }
}
