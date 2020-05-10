<?php
namespace Tests\Feature\Http\Api\Admin;

use App\PromoCode\QuantityType;
use App\Repositories\PromoCodeRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class PromoCodeCollectionTest extends HttpTestCase
{
    /** @var PromoCodeRepository */
    private $promoCodeRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->promoCodeRepository = $this->app->make(PromoCodeRepository::class);
    }

    /** @test */
    public function creates_promo_code()
    {
        // given
        $server = $this->factory->server();
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/promo_codes", [
            "code" => "abcpo",
            "quantity" => 20,
            "quantity_type" => "percentage",
            "server_id" => $server->getId(),
            "service_id" => "vippro",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $serviceCode = $this->promoCodeRepository->get($json["data"]["id"]);
        $this->assertNotNull($serviceCode);
        $this->assertSame("abcpo", $serviceCode->getCode());
        $this->assertSameEnum(QuantityType::PERCENTAGE(), $serviceCode->getQuantityType());
        $this->assertSame(20, $serviceCode->getQuantity());
        $this->assertNull($serviceCode->getUid());
        $this->assertSame($server->getId(), $serviceCode->getServerId());
        $this->assertSame("vippro", $serviceCode->getServiceId());
        $this->assertNotNull($serviceCode->getTimestamp());
    }

    /** @test */
    public function fails_with_invalid_data()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/service_codes", [
            "quantity_type" => "asd",
            "quantity" => "asd",
            "server_id" => "asd",
            "service_id" => "asd",
            "uid" => "asd",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertSame(
            [
                "code" =>
                    "<ul class=\"form_warning help is-danger\"><li >Pole nie może być puste.</li></ul>",
                "quantity" =>
                    "<ul class=\"form_warning help is-danger\"><li >Pole musi być liczbą całkowitą.</li></ul>",
                "uid" =>
                    "<ul class=\"form_warning help is-danger\"><li >Podane ID użytkownika nie jest przypisane do żadnego konta.</li></ul>",
                "server_id" =>
                    "<ul class=\"form_warning help is-danger\"><li >Brak serwera o takim ID.</li></ul>",
                "service_id" =>
                    "<ul class=\"form_warning help is-danger\"><li >Brak usługi o takim ID.</li></ul>",
            ],
            $json["warnings"]
        );
    }
}
