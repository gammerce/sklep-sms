<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PagePromoCodesActionBoxViewTest extends HttpTestCase
{
    /** @test */
    public function get_view_box()
    {
        // give
        $this->actingAs($this->factory->admin());
        $promoCode = $this->factory->promoCode([
            "server_id" => $this->factory->server()->getId(),
            "service_id" => $this->factory->extraFlagService()->getId(),
            "user_id" => $this->factory->user()->getId(),
        ]);

        // when
        $response = $this->get("/api/admin/pages/promo_codes/action_boxes/view", [
            "id" => $promoCode->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertContains("Kod promocyjny", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/promo_codes/action_boxes/view");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
