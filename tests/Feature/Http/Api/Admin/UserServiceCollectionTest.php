<?php
namespace Tests\Feature\Http\Api\Admin;

use App\ServiceModules\ExtraFlags\ExtraFlagUserService;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Services\UserServiceService;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceCollectionTest extends HttpTestCase
{
    /** @var UserServiceService */
    private $userServiceService;

    protected function setUp()
    {
        parent::setUp();

        $this->userServiceService = $this->app->make(UserServiceService::class);
        $this->actingAs($this->factory->admin());
    }

    /** @test */
    public function add_user_service()
    {
        // given
        $server = $this->factory->server();

        // when
        $response = $this->post("/api/admin/services/vip/user_services", [
            'type' => (string) ExtraFlagType::TYPE_NICK,
            'auth_data' => 'michal',
            'password' => 'abc123',
            'quantity' => '5',
            'server_id' => $server->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("ok", $json['return_id']);

        $userServices = $this->userServiceService->find();
        $this->assertCount(1, $userServices);

        /** @var ExtraFlagUserService $userService */
        $userService = $userServices[0];
        $this->assertSame('vip', $userService->getServiceId());
        $this->assertSame(ExtraFlagType::TYPE_NICK, $userService->getType());
        $this->assertSame('michal', $userService->getAuthData());
        $this->assertSame('abc123', $userService->getPassword());
        $this->assertSame($server->getId(), $userService->getServerId());
        $this->assertSame(0, $userService->getUid());
        $this->assertAlmostSameTimestamp(time() + 5 * 24 * 60 * 60, $userService->getExpire());
    }

    /** @test */
    public function adding_the_same_user_service_twice_prolongs_it()
    {
        // given
        $server = $this->factory->server();

        // when
        $this->post("/api/admin/services/vip/user_services", [
            'type' => (string) ExtraFlagType::TYPE_NICK,
            'auth_data' => 'michal',
            'password' => 'abc123',
            'quantity' => '5',
            'server_id' => $server->getId(),
        ]);
        $this->post("/api/admin/services/vip/user_services", [
            'type' => (string) ExtraFlagType::TYPE_NICK,
            'auth_data' => 'michal',
            'password' => 'abc123',
            'quantity' => '6',
            'server_id' => $server->getId(),
        ]);

        // then
        $userServices = $this->userServiceService->find();
        $this->assertCount(1, $userServices);
        $this->assertAlmostSameTimestamp(time() + 11 * 24 * 60 * 60, $userServices[0]->getExpire());
    }

    /** @test */
    public function special_characters_make_two_user_services_distinguishable()
    {
        // given
        $server = $this->factory->server();

        // when
        $this->post("/api/admin/services/vip/user_services", [
            'type' => (string) ExtraFlagType::TYPE_NICK,
            'auth_data' => 'michass',
            'password' => 'abc123',
            'quantity' => '5',
            'server_id' => $server->getId(),
        ]);
        $this->post("/api/admin/services/vip/user_services", [
            'type' => (string) ExtraFlagType::TYPE_NICK,
            'auth_data' => 'michaśś',
            'password' => 'abc123',
            'quantity' => '6',
            'server_id' => $server->getId(),
        ]);

        // then
        $userServices = $this->userServiceService->find();
        $this->assertCount(2, $userServices);
        $this->assertAlmostSameTimestamp(time() + 6 * 24 * 60 * 60, $userServices[0]->getExpire());
        $this->assertAlmostSameTimestamp(time() + 5 * 24 * 60 * 60, $userServices[1]->getExpire());
    }
}
