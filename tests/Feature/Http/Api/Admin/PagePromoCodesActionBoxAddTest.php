<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PagePromoCodesActionBoxAddTest extends HttpTestCase
{
    /** @test */
    public function get_add_box()
    {
        // give
        $this->actingAs($this->factory->admin());
        $this->factory->extraFlagService();
        $this->factory->server();

        // when
        $response = $this->get("/api/admin/pages/promo_codes/action_boxes/add");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertStringContainsString("Dodaj kod promocyjny", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/promo_codes/action_boxes/add");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
