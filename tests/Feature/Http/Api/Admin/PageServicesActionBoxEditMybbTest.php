<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PageServicesActionBoxEditMybbTest extends HttpTestCase
{
    /** @test */
    public function get_edit_box()
    {
        // give
        $service = $this->factory->mybbService();
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->getJson("/api/admin/pages/services/action_boxes/edit", [
            "id" => $service->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertContains("Edytuj usługę", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $service = $this->factory->mybbService();
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/services/action_boxes/edit", [
            "id" => $service->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
