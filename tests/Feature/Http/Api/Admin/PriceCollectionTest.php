<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\PriceRepository;
use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class PriceCollectionTest extends HttpTestCase
{
    private PriceRepository $priceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceRepository = $this->app->make(PriceRepository::class);

        $this->actingAs(
            $this->factory->privilegedUser([Permission::ACP(), Permission::PRICING_MANAGEMENT()])
        );
    }

    /** @test */
    public function creates_price()
    {
        // given
        $server = $this->factory->server();

        // when
        $response = $this->post("/api/admin/prices", [
            "service_id" => "vip",
            "server_id" => $server->getId(),
            "sms_price" => 200,
            "quantity" => 20,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $price = $this->priceRepository->get($json["data"]["id"]);
        $this->assertNotNull($price);
        $this->assertEqualsMoney(200, $price->getSmsPrice());
        $this->assertSame(20, $price->getQuantity());
        $this->assertSame($server->getId(), $price->getServerId());
        $this->assertSame("vip", $price->getServiceId());
    }

    /** @test */
    public function cannot_create_twice_the_same_price()
    {
        $server = $this->factory->server();

        $body = [
            "service_id" => "vip",
            "server_id" => $server->getId(),
            "sms_price" => 200,
            "quantity" => 20,
        ];

        $this->post("/api/admin/prices", $body);

        // when
        $response = $this->post("/api/admin/prices", $body);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("error", $json["return_id"]);
        $this->assertSame("Istnieje już cena dla tego serwera i tej ilości.", $json["text"]);
    }
}
