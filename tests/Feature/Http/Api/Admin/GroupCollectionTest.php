<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\GroupRepository;
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
            'name' => 'example',
            'view_player_flags' => true,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $group = $this->groupRepository->get($json["data"]["id"]);
        $this->assertNotNull($group);
        $this->assertSame("example", $group->getName());
        $this->assertEquals(
            [
                'acp' => false,
                'manage_settings' => false,
                'view_groups' => false,
                'manage_groups' => false,
                'view_player_flags' => true,
                'view_user_services' => false,
                'manage_user_services' => false,
                'view_income' => false,
                'view_users' => false,
                'manage_users' => false,
                'view_sms_codes' => false,
                'manage_sms_codes' => false,
                'view_service_codes' => false,
                'manage_service_codes' => false,
                'view_antispam_questions' => false,
                'manage_antispam_questions' => false,
                'view_services' => false,
                'manage_services' => false,
                'view_servers' => false,
                'manage_servers' => false,
                'view_logs' => false,
                'manage_logs' => false,
                'update' => false,
            ],
            $group->getPermissions()
        );
    }
}
