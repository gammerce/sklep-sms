<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\User;
use App\Repositories\UserRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class UserResourceTest extends HttpTestCase
{
    /** @var User */
    private $user;

    /** @var UserRepository */
    private $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->app->make(UserRepository::class);
        $this->user = $this->factory->user();
    }

    /** @test */
    public function updates_user()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->put("/api/admin/users/{$this->user->getUid()}", [
            "email" => 'example@example.com',
            "groups" => [1],
            "username" => "myabc",
            "wallet" => 20,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);

        $freshUser = $this->repository->get($this->user->getUid());
        $this->assertSame("example@example.com", $freshUser->getEmail());
        $this->assertSame("", $freshUser->getForename());
        $this->assertSame("", $freshUser->getSurname());
        $this->assertSame("", $freshUser->getSteamId());
        $this->assertSame(["1"], $freshUser->getGroups());
        $this->assertSame("myabc", $freshUser->getUsername());
        $this->assertSame(2000, $freshUser->getWallet());
    }

    /** @test */
    public function cannot_use_the_same_username_email_steam_id_twice()
    {
        // given
        $this->actingAs($this->factory->admin());

        $steamId = "STEAM_1:0:20340372";
        $username = "my_username";
        $email = "example@example.com";
        $this->factory->user([
            'steam_id' => $steamId,
            'username' => $username,
            'email' => $email,
        ]);

        // when
        $response = $this->put("/api/admin/users/{$this->user->getUid()}", [
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
                "email" =>
                    "<ul class=\"form_warning help is-danger\"><li >Podany e-mail jest już zajęty.</li></ul>",
                "steam_id" =>
                    "<ul class=\"form_warning help is-danger\"><li >Podany SteamID jest już przypisany do innego konta.</li></ul>",
                "username" =>
                    "<ul class=\"form_warning help is-danger\"><li >Podana nazwa użytkownika jest już zajęta.</li></ul>",
            ],
            $json["warnings"]
        );
    }
}
