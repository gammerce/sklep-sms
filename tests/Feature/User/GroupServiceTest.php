<?php
namespace Tests\Feature\User;

use App\User\GroupService;
use App\User\Permission;
use Tests\Psr4\TestCases\TestCase;

class GroupServiceTest extends TestCase
{
    private GroupService $groupService;

    public function setUp(): void
    {
        parent::setUp();
        $this->groupService = $this->app->make(GroupService::class);
    }

    /** @test */
    public function user_cannot_assign_a_group_without_a_permission()
    {
        // given
        $user = $this->factory->user();
        $group = $this->factory->group([
            "permissions" => [],
        ]);

        // when
        $result = $this->groupService->canUserAssignGroup($user, $group);

        // then
        $this->assertFalse($result);
    }

    /** @test */
    public function user_cannot_assign_a_group_when_does_not_have_every_group_permission()
    {
        // given
        $sales = $this->factory->group([
            "permissions" => [Permission::MANAGE_SERVERS(), Permission::MANAGE_SERVICES()],
        ]);
        $developers = $this->factory->group([
            "permissions" => [
                Permission::MANAGE_USERS(),
                Permission::MANAGE_SERVERS(),
                Permission::MANAGE_SMS_CODES(),
            ],
        ]);
        $user = $this->factory->user([
            "groups" => $developers->getId(),
        ]);

        // when
        $result = $this->groupService->canUserAssignGroup($user, $sales);

        // then
        $this->assertFalse($result);
    }

    /** @test */
    public function user_can_assign_a_group_when_have_every_group_permission()
    {
        // given
        $sales = $this->factory->group([
            "permissions" => [Permission::MANAGE_SERVERS(), Permission::MANAGE_SMS_CODES()],
        ]);
        $developers = $this->factory->group([
            "permissions" => [
                Permission::MANAGE_USERS(),
                Permission::MANAGE_SERVERS(),
                Permission::MANAGE_SMS_CODES(),
            ],
        ]);
        $user = $this->factory->user([
            "groups" => $developers->getId(),
        ]);

        // when
        $result = $this->groupService->canUserAssignGroup($user, $sales);

        // then
        $this->assertTrue($result);
    }
}
