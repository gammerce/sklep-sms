<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PagePricelistActionBoxAddTest extends HttpTestCase
{
    /** @test */
    public function get_add_box()
    {
        // give
        $admin = $this->factory->admin();
        $this->actAs($admin);

        // when
        $response = $this->get("/api/admin/pages/pricelist/action_boxes/price_add");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals('ok', $json['return_id']);
        $this->assertContains("Dodaj cenę", $json['template']);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $admin = $this->factory->user();
        $this->actAs($admin);

        // when
        $response = $this->get("/api/admin/pages/pricelist/action_boxes/price_add");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals('no_access', $json["return_id"]);
    }
}
