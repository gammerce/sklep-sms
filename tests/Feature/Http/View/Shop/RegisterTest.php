<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class RegisterTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // when
        $response = $this->get('/page/register');

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Rejestracja', $response->getContent());
    }
}
