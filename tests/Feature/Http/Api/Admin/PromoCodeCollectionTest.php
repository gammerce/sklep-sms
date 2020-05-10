<?php
namespace Tests\Feature\Http\Api\Admin;

use App\PromoCode\QuantityType;
use App\Repositories\PromoCodeRepository;
use DateTime;
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
        $promoCode = $this->promoCodeRepository->get($json["data"]["id"]);
        $this->assertNotNull($promoCode);
        $this->assertSame("abcpo", $promoCode->getCode());
        $this->assertSameEnum(QuantityType::PERCENTAGE(), $promoCode->getQuantityType());
        $this->assertSame(20, $promoCode->getQuantity());
        $this->assertNull($promoCode->getUserId());
        $this->assertSame($server->getId(), $promoCode->getServerId());
        $this->assertSame("vippro", $promoCode->getServiceId());
        $this->assertInstanceOf(DateTime::class, $promoCode->getCreatedAt());
        $this->assertNull($promoCode->getUsageCount());
        $this->assertNull($promoCode->getUsageLimit());
        $this->assertNull($promoCode->getExpiresAt());
    }

    /** @test */
    public function fails_with_invalid_data()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/promo_codes", [
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
