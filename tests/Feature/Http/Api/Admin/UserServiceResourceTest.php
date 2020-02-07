<?php
namespace Tests\Feature\Http\Api\Admin;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\ExtraFlags\ExtraFlagUserServiceRepository;
use App\ServiceModules\ExtraFlags\PlayerFlagRepository;
use App\Support\Database;
use Tests\Psr4\Concerns\PlayerFlagConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceResourceTest extends HttpTestCase
{
    use PlayerFlagConcern;

    /** @var ExtraFlagUserServiceRepository */
    private $extraFlagUserServiceRepository;

    /** @var PlayerFlagRepository */
    private $playerFlagRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->extraFlagUserServiceRepository = $this->app->make(
            ExtraFlagUserServiceRepository::class
        );
        $this->playerFlagRepository = $this->app->make(PlayerFlagRepository::class);
    }

    /** @test */
    public function updates_user_service()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($this->factory->admin());

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'server_id' => $server->getId(),
        ]);
        $expireTimestamp = time() + 24321;

        // when
        $response = $this->put("/api/admin/user_services/{$userService->getId()}", [
            'auth_data' => 'STEAM_1:1:21984552',
            'type' => ExtraFlagType::TYPE_SID,
            'expire' => convert_date($expireTimestamp, "Y-m-d H:i:s"),
            'server_id' => $server->getId(),
            'service_id' => 'vip',
            'uid' => $user->getUid(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertSame(ExtraFlagType::TYPE_SID, $freshUserService->getType());
        $this->assertSame('STEAM_1:1:21984552', $freshUserService->getAuthData());
        $this->assertSame('', $freshUserService->getPassword());
        $this->assertSame($user->getUid(), $freshUserService->getUid());
        $this->assertSame($expireTimestamp, $freshUserService->getExpire());
        $playerFlag = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            ExtraFlagType::TYPE_SID,
            'STEAM_1:1:21984552'
        );
        $this->assertNotNull($playerFlag);
        $this->assertSame(ExtraFlagType::TYPE_SID, $playerFlag->getType());
        $this->assertSame('STEAM_1:1:21984552', $playerFlag->getAuthData());
        $this->assertSame("", $playerFlag->getPassword());
        $this->assertSame($server->getId(), $playerFlag->getServerId());
        $this->assertPlayerFlags(["t" => $expireTimestamp], $playerFlag->getFlags());
    }

    /** @test */
    public function makes_user_service_last_forever()
    {
        // given
        $this->actingAs($this->factory->admin());

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'server_id' => $server->getId(),
        ]);

        // when
        $response = $this->put("/api/admin/user_services/{$userService->getId()}", [
            'auth_data' => 'STEAM_1:1:21984552',
            'type' => ExtraFlagType::TYPE_SID,
            'forever' => 'on',
            'server_id' => $server->getId(),
            'service_id' => 'vip',
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertSame(-1, $freshUserService->getExpire());
        $playerFlag = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            ExtraFlagType::TYPE_SID,
            'STEAM_1:1:21984552'
        );
        $this->assertPlayerFlags(["t" => -1], $playerFlag->getFlags());
    }

    /** @test */
    public function password_is_not_updated()
    {
        // given
        $this->actingAs($this->factory->admin());

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'type' => ExtraFlagType::TYPE_NICK,
            'auth_data' => "my_nick",
            'password' => "my_password",
            'server_id' => $server->getId(),
        ]);

        // when
        $response = $this->put("/api/admin/user_services/{$userService->getId()}", [
            'type' => ExtraFlagType::TYPE_NICK,
            'auth_data' => 'my_nick2',
            'expire' => convert_date($userService->getExpire() + 2),
            'server_id' => $server->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertSame("my_password", $freshUserService->getPassword());
        $playerFlag = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            ExtraFlagType::TYPE_NICK,
            'my_nick2'
        );
        $this->assertSame("my_password", $playerFlag->getPassword());
    }

    /** @test */
    public function changes_user_service_service()
    {
        // given
        $this->actingAs($this->factory->admin());

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'server_id' => $server->getId(),
        ]);

        // when
        $response = $this->put("/api/admin/user_services/{$userService->getId()}", [
            'auth_data' => 'STEAM_1:1:21984552',
            'type' => ExtraFlagType::TYPE_SID,
            'forever' => 'on',
            'server_id' => $server->getId(),
            'service_id' => 'vippro',
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertSame("vippro", $freshUserService->getServiceId());
        $playerFlag = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            ExtraFlagType::TYPE_SID,
            'STEAM_1:1:21984552'
        );
        $this->assertPlayerFlags(["b" => -1, "t" => -1, "x" => -1], $playerFlag->getFlags());
    }

    /** @test */
    public function fails_when_invalid_data_is_passed()
    {
        // given
        $this->actingAs($this->factory->admin());

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'server_id' => $server->getId(),
        ]);

        // when
        $response = $this->put("/api/admin/user_services/{$userService->getId()}", [
            'auth_data' => 'sd',
            'password' => 'ab12a',
            'type' => ExtraFlagType::TYPE_IP,
            'service_id' => 'vip',
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "auth_data" =>
                    '<ul class="form_warning help is-danger"><li >Wprowadzony adres IP jest nieprawidłowy.</li></ul>',
                "expire" =>
                    '<ul class="form_warning help is-danger"><li >Pole nie może być puste.</li></ul>',
                "server_id" =>
                    '<ul class="form_warning help is-danger"><li >Pole nie może być puste.</li></ul>',
            ],
            $json["warnings"]
        );
    }

    /** @test */
    public function deletes_user_service()
    {
        // given
        $this->actingAs($this->factory->admin());
        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'server_id' => $server->getId(),
        ]);

        // when
        $response = $this->delete("/api/admin/user_services/{$userService->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertNull($freshUserService);
        $playerFlag = $this->playerFlagRepository->getByCredentials(
            $server->getId(),
            $userService->getType(),
            $userService->getAuthData()
        );
        $this->assertNull($playerFlag);
    }
}
