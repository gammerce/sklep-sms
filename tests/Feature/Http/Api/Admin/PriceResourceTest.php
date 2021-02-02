<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\PriceRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class PriceResourceTest extends HttpTestCase
{
    private PriceRepository $priceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceRepository = $this->app->make(PriceRepository::class);
    }

    /** @test */
    public function updates_price()
    {
        // given
        $this->actingAs($this->factory->admin());
        $price = $this->factory->price();

        // when
        $response = $this->put("/api/admin/prices/{$price->getId()}", [
            "service_id" => "vippro",
            "server_id" => null,
            "sms_price" => 300,
            "quantity" => 30,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshPrice = $this->priceRepository->get($price->getId());
        $this->assertNotNull($freshPrice);
        $this->assertEqualsMoney(300, $freshPrice->getSmsPrice());
        $this->assertSame(30, $freshPrice->getQuantity());
        $this->assertNull($freshPrice->getServerId());
        $this->assertSame("vippro", $freshPrice->getServiceId());
    }

    /** @test */
    public function deletes_price()
    {
        $this->actingAs($this->factory->admin());
        $price = $this->factory->price();

        // when
        $response = $this->delete("/api/admin/prices/{$price->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshPrice = $this->priceRepository->get($price->getId());
        $this->assertNull($freshPrice);
    }
}
