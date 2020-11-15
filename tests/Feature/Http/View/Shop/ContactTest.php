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
        $response = $this->get("/page/contact");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("Kontakt", $response->getContent());
    }
}
