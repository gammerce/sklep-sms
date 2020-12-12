<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
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
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("VIP - Zakup usługi", $response->getContent());
    }
}
