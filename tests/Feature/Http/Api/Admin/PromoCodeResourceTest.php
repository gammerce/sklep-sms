<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\PromoCodeRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class PromoCodeResourceTest extends HttpTestCase
{
    private PromoCodeRepository $promoCodeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promoCodeRepository = $this->app->make(PromoCodeRepository::class);
    }

    /** @test */
    public function deletes_price()
    {
        $this->actingAs($this->factory->admin());
        $promoCode = $this->factory->promoCode();

        // when
        $response = $this->delete("/api/admin/promo_codes/{$promoCode->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshPromoCode = $this->promoCodeRepository->get($promoCode->getId());
        $this->assertNull($freshPromoCode);
    }
}
