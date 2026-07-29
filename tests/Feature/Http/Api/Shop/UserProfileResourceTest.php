<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Repositories\UserRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class UserProfileResourceTest extends HttpTestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->make(UserRepository::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv("APP_SUBDOMAIN");
    }

    /** @test */
    public function updates_profile()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->put("/api/profile", [
            "username" => "abc",
            "forename" => "poq",
            "surname" => "wer",
            "steam_id" => "STEAM_1:0:22309350",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame("abc", $freshUser->getUsername());
        $this->assertSame("poq", $freshUser->getForename());
        $this->assertSame("wer", $freshUser->getSurname());
        $this->assertSame("STEAM_1:0:22309350", $freshUser->getSteamId());
    }

    /** @test */
    public function fails_with_invalid_data_passed()
    {
        // given
        $this->actingAs($this->factory->user());
        $this->factory->user([
            "username" => "abcaaa",
            "steam_id" => "STEAM_1:0:22309350",
        ]);

        // when
        $response = $this->put("/api/profile", [
            "username" => "abcaaa",
            "forename" => "poq",
            "surname" => "wer",
            "steam_id" => "STEAM_1:0:22309350",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "username" => ["Podana nazwa użytkownika jest już zajęta."],
                "steam_id" => ["Podany SteamID jest już przypisany do innego konta."],
            ],
            $json["warnings"]
        );
    }

    /** @test */
    public function cannot_update_profile_as_admin_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $admin = $this->userRepository->get(1);
        $this->actingAs($admin);

        $response = $this->put("/api/profile", [
            "username" => "newname",
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("demo_mode", $json["return_id"]);
    }

    /** @test */
    public function can_update_profile_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $user = $this->factory->user();
        $this->actingAs($user);

        $response = $this->put("/api/profile", [
            "username" => "newname",
            "forename" => "newfn",
            "surname" => "newln",
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
    }
}
