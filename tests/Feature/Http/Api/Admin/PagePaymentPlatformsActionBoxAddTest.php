<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PagePaymentPlatformsActionBoxAddTest extends HttpTestCase
{
    /** @test */
    public function get_add_box()
    {
        // give
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/api/admin/pages/payment_platforms/action_boxes/create");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertStringContainsString("Dodaj platformę płatności", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/payment_platforms/action_boxes/create");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
