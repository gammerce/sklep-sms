<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class RegisterTest extends HttpTestCase
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
