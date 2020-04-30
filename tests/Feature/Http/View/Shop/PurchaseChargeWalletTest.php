<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseChargeWalletTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->get("/page/purchase", ["service" => "charge_wallet"]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("Doładowanie Portfela - Zakup usługi", $response->getContent());
    }

    /** @test */
    public function requires_being_logged()
    {
        // when
        $response = $this->get("/page/purchase", ["service" => "charge_wallet"]);

        // then
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith("/login", $response->headers->get("Location"));
    }
}
