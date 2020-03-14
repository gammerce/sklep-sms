<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class ServiceCodesTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());
        $this->factory->serviceCode([
            'price_id' => $this->factory->price()->getId(),
        ]);
        $this->factory->serviceCode([
            'price_id' => $this->factory->price()->getId(),
        ]);

        // when
        $response = $this->get('/admin/service_codes');

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
        $this->assertContains('<div class="title is-4">Kody na usługi', $response->getContent());
    }
}
