<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\IndexTestCase;

class ContactTest extends IndexTestCase
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
