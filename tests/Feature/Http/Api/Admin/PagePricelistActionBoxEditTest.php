<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PagePricelistActionBoxEditTest extends HttpTestCase
{
    /** @test */
    public function get_edit_box()
    {
        // give
        $server = $this->factory->server();
        $price = $this->factory->price([
            'server_id' => $server->getId(),
        ]);
        $admin = $this->factory->admin();
        $this->actingAs($admin);

        // when
        $response = $this->get("/api/admin/pages/pricelist/action_boxes/price_edit", [
            'id' => $price->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals('ok', $json['return_id']);
        $this->assertContains("Edytuj cenę", $json['template']);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $server = $this->factory->server();
        $price = $this->factory->price([
            'server_id' => $server->getId(),
        ]);
        $admin = $this->factory->user();
        $this->actingAs($admin);

        // when
        $response = $this->get("/api/admin/pages/pricelist/action_boxes/price_edit", [
            'id' => $price->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals('no_access', $json["return_id"]);
    }
}
