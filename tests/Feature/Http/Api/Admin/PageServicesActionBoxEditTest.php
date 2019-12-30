<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PageServicesActionBoxEditTest extends HttpTestCase
{
    /** @test */
    public function get_edit_box()
    {
        // give
        $service = $this->factory->service();
        $admin = $this->factory->admin();
        $this->actAs($admin);

        // when
        $response = $this->get("/api/admin/pages/services/action_boxes/service_edit", [
            "id" => $service->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals('ok', $json['return_id']);
        $this->assertContains("Edytuj usługę", $json['template']);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $service = $this->factory->service();
        $admin = $this->factory->user();
        $this->actAs($admin);

        // when
        $response = $this->get("/api/admin/pages/services/action_boxes/service_edit", [
            "id" => $service->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals('no_access', $json["return_id"]);
    }
}
