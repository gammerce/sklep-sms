<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\UserRepository;
use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class UserResourceTest extends HttpTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(UserRepository::class);
    }

    /** @test */
    public function updates_user()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->putJson("/api/admin/users/{$user->getId()}", [
            "email" => "example@example.com",
            "groups" => [1],
            "username" => "myabc",
            "wallet" => 20,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);

        $freshUser = $this->repository->get($user->getId());
        $this->assertSame("example@example.com", $freshUser->getEmail());
        $this->assertSame("", $freshUser->getForename());
        $this->assertSame("", $freshUser->getSurname());
        $this->assertNull($freshUser->getSteamId());
        $this->assertSame([1], $freshUser->getGroups());
        $this->assertSame("myabc", $freshUser->getUsername());
        $this->assertEqualsMoney(2000, $freshUser->getWallet());
    }

    /** @test */
    public function cannot_use_the_same_username_email_steam_id_twice()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($this->factory->admin());

        $steamId = "STEAM_1:0:20340372";
        $username = "my_username";
        $email = "example@example.com";
        $this->factory->user([
            "steam_id" => $steamId,
            "username" => $username,
            "email" => $email,
        ]);

        // when
        $response = $this->putJson("/api/admin/users/{$user->getId()}", [
            "email" => $email,
            "groups" => [1],
            "username" => $username,
            "steam_id" => $steamId,
            "wallet" => 20,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "email" => ["Podany e-mail jest już zajęty."],
                "steam_id" => ["Podany SteamID jest już przypisany do innego konta."],
                "username" => ["Podana nazwa użytkownika jest już zajęta."],
            ],
            $json["warnings"]
        );
    }

    /** @test */
    public function cannot_extend_own_permissions()
    {
        // given
        $miniAdmin = $this->factory->group([
            "permissions" => [Permission::ACP(), Permission::USERS_MANAGEMENT()],
        ]);
        $sales = $this->factory->group([
            "permissions" => [Permission::ACP(), Permission::SERVERS_MANAGEMENT()],
        ]);
        $user = $this->factory->user([
            "groups" => [$miniAdmin->getId()],
        ]);
        $this->actingAs($user);

        // when
        $response = $this->putJson("/api/admin/users/{$user->getId()}", [
            "email" => $user->getEmail(),
            "groups" => [$miniAdmin->getId(), $sales->getId()],
            "username" => $user->getUsername(),
            "steam_id" => $user->getSteamId(),
            "wallet" => $user->getWallet()->asFloat(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "groups" => ["Wybrano błędną grupę."],
            ],
            $json["warnings"]
        );
    }

    /** @test */
    public function cannot_update_groups_of_a_user_with_wider_permissions()
    {
        // given
        $sales = $this->factory->group([
            "permissions" => [Permission::ACP(), Permission::USERS_MANAGEMENT()],
        ]);
        $developers = $this->factory->group([
            "permissions" => [Permission::SERVICES_MANAGEMENT()],
        ]);
        $tom = $this->factory->user([
            "groups" => [$sales->getId()],
        ]);
        $frank = $this->factory->user([
            "groups" => [$developers->getId()],
        ]);
        $this->actingAs($tom);

        // when
        $response = $this->putJson("/api/admin/users/{$frank->getId()}", [
            "email" => $frank->getEmail(),
            "groups" => [$sales->getId()],
            "username" => $frank->getUsername(),
            "steam_id" => $frank->getSteamId(),
            "wallet" => $frank->getWallet()->asFloat(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);

        $freshFrank = $this->repository->get($frank->getId());
        $this->assertEquals([$developers->getId()], $freshFrank->getGroups());
    }
}
