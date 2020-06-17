<?php
namespace Tests\Feature\Http\Api\Shop;

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
        $this->actingAs($user);

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            "server_id" => $server->getId(),
            "user_id" => $user->getId(),
            "seconds" => 7 * 24 * 60 * 60,
        ]);

        // when
        $response = $this->put("/api/user_services/{$userService->getId()}", [
            "auth_data" => "192.0.2.5",
            "password" => "ab12ab",
            "type" => ExtraFlagType::TYPE_IP,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertSame("192.0.2.5", $freshUserService->getAuthData());
        $this->assertSame("ab12ab", $freshUserService->getPassword());
        $this->assertSame(ExtraFlagType::TYPE_IP, $freshUserService->getType());
        $this->assertAlmostSameTimestamp($userService->getExpire(), $freshUserService->getExpire());
    }

    /** @test */
    public function cannot_update_service_without_owner()
    {
        // given
        $this->actingAs($this->factory->user());

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            "server_id" => $server->getId(),
        ]);

        // when
        $response = $this->put("/api/user_services/{$userService->getId()}", [
            "auth_data" => "192.0.2.5",
            "password" => "ab12ab",
            "type" => ExtraFlagType::TYPE_IP,
        ]);

        // then
        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function fails_when_invalid_data_is_passed()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            "server_id" => $server->getId(),
            "user_id" => $user->getId(),
        ]);

        // when
        $response = $this->put("/api/user_services/{$userService->getId()}", [
            "auth_data" => "sd",
            "password" => "ab12a",
            "type" => ExtraFlagType::TYPE_IP,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "auth_data" => ["Wprowadzony adres IP jest nieprawidłowy."],
                "password" => ["Pole musi się składać z co najmniej 6 znaków."],
            ],
            $json["warnings"]
        );
    }
}
