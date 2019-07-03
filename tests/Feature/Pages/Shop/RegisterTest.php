<?php
namespace Tests\Feature\Pages\Shop;

use Tests\Psr4\TestCases\IndexTestCase;

class RegisterTest extends IndexTestCase
{
    /** @test */
    public function it_loads()
    {
        // given

        // when
        $response = $this->get('/', ['pid' => 'register']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Rejestracja', $response->getContent());
    }
}
