<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\Group;
use App\Repositories\GroupRepository;
use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class GroupResourceTest extends HttpTestCase
{
    private GroupRepository $groupRepository;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupRepository = $this->app->make(GroupRepository::class);
        $this->group = $this->factory->group();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv("APP_SUBDOMAIN");
    }

    /** @test */
    public function updates_group()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->put("/api/admin/groups/{$this->group->getId()}", [
            "name" => "example2",
            "permissions" => ["view_groups"],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshGroup = $this->groupRepository->get($this->group->getId());
        $this->assertSame("example2", $freshGroup->getName());
        $this->assertTrue($freshGroup->hasPermission(Permission::GROUPS_VIEW()));
    }

    /** @test */
    public function deletes_group()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->delete("/api/admin/groups/{$this->group->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshGroup = $this->groupRepository->get($this->group->getId());
        $this->assertNull($freshGroup);
    }

    /** @test */
    public function cannot_edit_group_1_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $this->actingAs($this->factory->admin());

        $response = $this->put("/api/admin/groups/1", [
            "name" => "changed",
            "permissions" => ["view_groups"],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("demo_mode", $json["return_id"]);
    }

    /** @test */
    public function cannot_edit_group_2_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $this->actingAs($this->factory->admin());

        $response = $this->put("/api/admin/groups/2", [
            "name" => "changed",
            "permissions" => ["view_groups"],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("demo_mode", $json["return_id"]);
    }

    /** @test */
    public function cannot_delete_group_1_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $this->actingAs($this->factory->admin());

        $response = $this->delete("/api/admin/groups/1");

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("demo_mode", $json["return_id"]);
    }

    /** @test */
    public function cannot_delete_group_2_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $this->actingAs($this->factory->admin());

        $response = $this->delete("/api/admin/groups/2");

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("demo_mode", $json["return_id"]);
    }

    /** @test */
    public function can_edit_other_group_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $this->actingAs($this->factory->admin());
        $group = $this->factory->group();

        $response = $this->put("/api/admin/groups/{$group->getId()}", [
            "name" => "changed",
            "permissions" => ["view_groups"],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
    }

    /** @test */
    public function can_delete_other_group_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $this->actingAs($this->factory->admin());
        $group = $this->factory->group();

        $response = $this->delete("/api/admin/groups/{$group->getId()}");

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
    }
}
