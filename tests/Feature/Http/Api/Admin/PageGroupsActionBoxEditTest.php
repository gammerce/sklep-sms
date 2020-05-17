<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\Group;
use Tests\Psr4\TestCases\HttpTestCase;

class PageGroupsActionBoxEditTest extends HttpTestCase
{
    /** @var Group */
    private $group;

    protected function setUp()
    {
        parent::setUp();
        $this->group = $this->factory->group();
    }

    /** @test */
    public function get_edit_box()
    {
        // give
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/api/admin/pages/groups/action_boxes/edit", [
            "id" => $this->group->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertContains("Edytuj grupę", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/groups/action_boxes/edit", [
            "id" => $this->group->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
