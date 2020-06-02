<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseExtraFlagTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->user());

        // when
        $response = $this->get("/page/purchase", ["service" => "vip"]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("VIP - Zakup usługi", $response->getContent());
    }
}
