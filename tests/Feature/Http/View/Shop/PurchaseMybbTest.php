<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
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
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("{$service->getNameI18n()} - Zakup usługi", $response->getContent());
    }
}
