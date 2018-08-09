<?php
namespace Tests\Feature\Pages\Shop;

use Tests\Psr4\TestCases\IndexTestCase;

class HomepageTest extends IndexTestCase
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