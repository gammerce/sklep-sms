<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PageUsersActionBoxChangePasswordTest extends HttpTestCase
{
    /** @test */
    public function get_change_password_box()
    {
        // give
        $admin = $this->factory->admin();
        $this->actingAs($admin);

        // when
        $response = $this->get("/api/admin/pages/users/action_boxes/change_password", [
            "uid" => $admin->getUid(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals('ok', $json['return_id']);
        $this->assertContains("Zmiana hasła", $json['template']);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $admin = $this->factory->user();
        $this->actingAs($admin);

        // when
        $response = $this->getJson("/api/admin/pages/servers/action_boxes/change_password", [
            "uid" => $admin->getUid(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals('no_access', $json["return_id"]);
    }
}
