<?php
namespace Tests\Feature\Http\Api\Admin;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\ExtraFlags\ExtraFlagUserService;
use App\ServiceModules\ExtraFlags\PlayerFlagRepository;
use App\Service\UserServiceService;
use Tests\Psr4\Concerns\PlayerFlagConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceCollectionExtraFlagTest extends HttpTestCase
{
    use PlayerFlagConcern;

    private UserServiceService $userServiceService;
    private PlayerFlagRepository $playerFlagRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userServiceService = $this->app->make(UserServiceService::class);
        $this->playerFlagRepository = $this->app->make(PlayerFlagRepository::class);
        $this->actingAs($this->factory->admin());
    }

    /** @test */
    public function add_user_service()
    {
        // given
        $expectedExpire = time() + 5 * 24 * 60 * 60;
        $server = $this->factory->server();

        // when
        $response = $this->post("/api/admin/services/vip/user_services", [
            "auth_data" => "michal",
            "comment" => "my comment",
            "password" => "abc123",
            "quantity" => "5",
            "server_id" => $server->getId(),
            "type" => ExtraFlagType::TYPE_NICK,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("ok", $json["return_id"]);

        /** @var ExtraFlagUserService $userService */
        $userService = $this->userServiceService->find()[0];
        $this->assertNotNull($userService);
        $this->assertSame("vip", $userService->getServiceId());
        $this->assertSame(ExtraFlagType::TYPE_NICK, $userService->getType());
        $this->assertSame("michal", $userService->getAuthData());
        $this->assertSame("abc123", $userService->getPassword());
        $this->assertSame($server->getId(), $userService->getServerId());
        $this->assertSame(0, $userService->getUserId());
        $this->assertAlmostSameTimestamp($expectedExpire, $userService->getExpire());
        $this->assertEquals("my comment", $userService->getComment());

        $playerFlag = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            ExtraFlagType::TYPE_NICK,
            "michal"
        );
        $this->assertNotNull($playerFlag);
        $this->assertSame(ExtraFlagType::TYPE_NICK, $playerFlag->getType());
        $this->assertSame("michal", $playerFlag->getAuthData());
        $this->assertSame("abc123", $playerFlag->getPassword());
        $this->assertSame($server->getId(), $playerFlag->getServerId());
        $this->assertPlayerFlags(["t" => $expectedExpire], $playerFlag->getFlags());
    }

    /** @test */
    public function adding_the_same_user_service_twice_prolongs_it()
    {
        // given
        $expectedExpire = time() + 11 * 24 * 60 * 60;
        $server = $this->factory->server();

        // when
        $this->post("/api/admin/services/vip/user_services", [
            "auth_data" => "michal",
            "comment" => "foo",
            "password" => "abc123",
            "quantity" => "5",
            "server_id" => $server->getId(),
            "type" => (string) ExtraFlagType::TYPE_NICK,
        ]);
        $this->post("/api/admin/services/vip/user_services", [
            "auth_data" => "michal",
            "comment" => "bar",
            "password" => "abc123",
            "quantity" => "6",
            "server_id" => $server->getId(),
            "type" => (string) ExtraFlagType::TYPE_NICK,
        ]);

        // then
        $userServices = $this->userServiceService->find();
        $this->assertCount(1, $userServices);
        $this->assertAlmostSameTimestamp($expectedExpire, $userServices[0]->getExpire());
        $this->assertEquals("foo\n---\nbar", $userServices[0]->getComment());

        $playerFlag = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            ExtraFlagType::TYPE_NICK,
            "michal"
        );
        $this->assertNotNull($playerFlag);
        $this->assertPlayerFlags(["t" => $expectedExpire], $playerFlag->getFlags());
    }

    /** @test */
    public function special_characters_make_two_user_services_distinguishable()
    {
        // given
        $server = $this->factory->server();

        // when
        $this->post("/api/admin/services/vip/user_services", [
            "type" => (string) ExtraFlagType::TYPE_NICK,
            "auth_data" => "michass",
            "password" => "abc123",
            "quantity" => "5",
            "server_id" => $server->getId(),
        ]);
        $this->post("/api/admin/services/vip/user_services", [
            "type" => (string) ExtraFlagType::TYPE_NICK,
            "auth_data" => "michaśś",
            "password" => "abc123",
            "quantity" => "6",
            "server_id" => $server->getId(),
        ]);

        // then
        $userServices = $this->userServiceService->find();
        $this->assertCount(2, $userServices);
        $this->assertAlmostSameTimestamp(time() + 6 * 24 * 60 * 60, $userServices[0]->getExpire());
        $this->assertAlmostSameTimestamp(time() + 5 * 24 * 60 * 60, $userServices[1]->getExpire());

        $playerFlag = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            ExtraFlagType::TYPE_NICK,
            "michass"
        );
        $this->assertNotNull($playerFlag);
        $this->assertSame("michass", $playerFlag->getAuthData());

        $playerFlagSpecialChars = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            ExtraFlagType::TYPE_NICK,
            "michaśś"
        );
        $this->assertNotNull($playerFlagSpecialChars);
        $this->assertSame("michaśś", $playerFlagSpecialChars->getAuthData());
    }
}
