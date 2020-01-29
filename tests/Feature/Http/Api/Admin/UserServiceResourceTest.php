<?php
namespace Tests\Feature\Http\Api\Admin;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\ExtraFlags\ExtraFlagUserServiceRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceResourceTest extends HttpTestCase
{
    /** @var ExtraFlagUserServiceRepository */
    private $extraFlagUserServiceRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->extraFlagUserServiceRepository = $this->app->make(
            ExtraFlagUserServiceRepository::class
        );
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
            'uid' => $user->getUid(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertSame('STEAM_1:1:21984552', $freshUserService->getAuthData());
        $this->assertSame(ExtraFlagType::TYPE_SID, $freshUserService->getType());
        $this->assertSame($user->getUid(), $freshUserService->getUid());
        $this->assertSame($expireTimestamp, $freshUserService->getExpire());
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
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertSame(-1, $freshUserService->getExpire());
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
    }
}
