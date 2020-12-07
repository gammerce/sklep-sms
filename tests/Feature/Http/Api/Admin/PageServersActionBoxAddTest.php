<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PageServersActionBoxAddTest extends HttpTestCase
{
    /** @test */
    public function get_add_box()
    {
        // give
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->getJson("/api/admin/pages/servers/action_boxes/add");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertStringContainsString("Dodaj serwer", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/servers/action_boxes/add");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
