<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class ContactTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given

        // when
        $response = $this->get('/', ['pid' => 'contact']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Kontakt', $response->getContent());
    }
}
