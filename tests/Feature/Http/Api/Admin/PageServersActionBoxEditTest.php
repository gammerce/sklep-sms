<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PageServersActionBoxEditTest extends HttpTestCase
{
    /** @test */
    public function get_edit_box()
    {
        // give
        $server = $this->factory->server();
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->getJson("/api/admin/pages/servers/action_boxes/edit", [
            "id" => $server->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertContains("Edytuj serwer", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $server = $this->factory->server();
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/servers/action_boxes/edit", [
            "id" => $server->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
