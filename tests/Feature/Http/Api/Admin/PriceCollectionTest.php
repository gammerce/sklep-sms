<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\PriceRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class PriceCollectionTest extends HttpTestCase
{
    /** @var PriceRepository */
    private $priceRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->priceRepository = $this->app->make(PriceRepository::class);
    }

    /** @test */
    public function creates_price()
    {
        // given
        $server = $this->factory->server();
        $admin = $this->factory->admin();
        $this->actingAs($admin);

        // when
        $response = $this->post("/api/admin/prices", [
            'service' => 'vip',
            'server' => $server->getId(),
            'tariff' => 2,
            'amount' => 20,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $price = $this->priceRepository->get($json['data']['id']);
        $this->assertNotNull($price);
        $this->assertSame(2, $price->getTariff());
        $this->assertSame(20, $price->getAmount());
        $this->assertSame($server->getId(), $price->getServer());
        $this->assertSame('vip', $price->getService());
    }

    /** @test */
    public function cannot_create_twice_the_same_price()
    {
        $server = $this->factory->server();
        $admin = $this->factory->admin();
        $this->actingAs($admin);

        $body = [
            'service' => 'vip',
            'server' => $server->getId(),
            'tariff' => 2,
            'amount' => 20,
        ];

        $this->post("/api/admin/prices", $body);

        // when
        $response = $this->post("/api/admin/prices", $body);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("error", $json["return_id"]);
        $this->assertSame("Istnieje już cena dla tego serwera i tej taryfy.", $json["text"]);
    }
}
