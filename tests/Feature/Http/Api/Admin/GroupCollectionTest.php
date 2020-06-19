<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\GroupRepository;
use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class GroupCollectionTest extends HttpTestCase
{
    /** @var GroupRepository */
    private $groupRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->groupRepository = $this->app->make(GroupRepository::class);
    }

    /** @test */
    public function creates_group()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/groups", [
            "name" => "example",
            "permissions" => ["view_player_flags", "manage_logs"],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $group = $this->groupRepository->get($json["data"]["id"]);
        $this->assertNotNull($group);
        $this->assertSame("example", $group->getName());
        $this->assertEquals(
            [Permission::VIEW_PLAYER_FLAGS(), Permission::MANAGE_LOGS()],
            $group->getPermissions()
        );
    }
}
