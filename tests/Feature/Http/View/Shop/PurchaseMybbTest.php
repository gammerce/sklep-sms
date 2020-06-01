<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseMybbTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $service = $this->factory->mybbService();
        $this->actingAs($this->factory->user());

        // when
        $response = $this->get("/page/purchase", ["service" => $service->getId()]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("{$service->getNameI18n()} - Zakup usługi", $response->getContent());
    }
}
