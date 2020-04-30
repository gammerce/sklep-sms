<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PageUsersActionBoxChargeWalletTest extends HttpTestCase
{
    /** @test */
    public function get_charge_wallet_box()
    {
        // give
        $admin = $this->factory->admin();
        $this->actingAs($admin);

        // when
        $response = $this->getJson("/api/admin/pages/users/action_boxes/charge_wallet", [
            "uid" => $admin->getUid(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertContains("Doładuj portfel", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $admin = $this->factory->user();
        $this->actingAs($admin);

        // when
        $response = $this->getJson("/api/admin/pages/servers/action_boxes/charge_wallet", [
            "uid" => $admin->getUid(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
