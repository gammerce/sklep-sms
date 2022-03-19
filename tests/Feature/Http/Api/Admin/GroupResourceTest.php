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
}
