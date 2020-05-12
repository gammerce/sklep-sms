<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PageUsersActionBoxEditTest extends HttpTestCase
{
    /** @test */
    public function get_edit_box()
    {
        // give
        $admin = $this->factory->admin();
        $this->actingAs($admin);

        // when
        $response = $this->getJson("/api/admin/pages/users/action_boxes/user_edit", [
            "user_id" => $admin->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertContains("Edytuj użytkownika", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $admin = $this->factory->user();
        $this->actingAs($admin);

        // when
        $response = $this->getJson("/api/admin/pages/servers/action_boxes/user_edit", [
            "user_id" => $admin->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
