<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PagePricingActionBoxEditTest extends HttpTestCase
{
    /** @test */
    public function get_edit_box()
    {
        // give
        $server = $this->factory->server();
        $price = $this->factory->price([
            "server_id" => $server->getId(),
        ]);
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/api/admin/pages/pricing/action_boxes/edit", [
            "id" => $price->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertStringContainsString("Edytuj cenę", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $server = $this->factory->server();
        $price = $this->factory->price([
            "server_id" => $server->getId(),
        ]);
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/pricing/action_boxes/edit", [
            "id" => $price->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
