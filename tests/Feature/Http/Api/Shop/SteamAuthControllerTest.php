<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Repositories\UserRepository;
use App\Requesting\Response as RequestingResponse;
use App\Support\SteamIDConverter;
use App\Support\SteamProfile;
use Mockery;
use Symfony\Component\HttpFoundation\Session\Session;
use Tests\Psr4\TestCases\HttpTestCase;

class SteamAuthControllerTest extends HttpTestCase
{
    private const STEAM_ID_64 = "76561197990444178";

    private UserRepository $userRepository;
    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->make(UserRepository::class);
        $this->session = $this->app->make(Session::class);
        putenv("STEAM_API_KEY");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv("STEAM_API_KEY");
    }

    /** @test */
    public function login_redirects_to_steam()
    {
        // when
        $response = $this->get("/api/steam/login");

        // then
        $this->assertSame(302, $response->getStatusCode());
        $location = (string) $response->headers->get("Location");
        $this->assertStringStartsWith("https://steamcommunity.com/openid/login?", $location);
        $this->assertStringContainsString("openid.mode=checkid_setup", $location);
        $this->assertStringContainsString("openid.return_to=", $location);
        $this->assertNotNull($this->session->get("steam_state"));
        $this->assertSame("login", $this->session->get("steam_intent"));
    }

    /** @test */
    public function logs_in_verified_steam_account()
    {
        // given
        $user = $this->factory->user();
        $this->userRepository->linkSteamId($user->getId(), $this->legacySteamId());

        $state = "teststate";
        $this->session->set("steam_state", $state);
        $this->mockSuccessfulAuthentication();

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/", $response->headers->get("Location"));
        $this->assertSame($user->getId(), $this->session->get("uid"));
    }

    /** @test */
    public function creates_account_with_a_sanitized_persona_username()
    {
        // given Steam reports a persona with emoji/space
        $this->app->instance(
            SteamProfile::class,
            Mockery::mock(SteamProfile::class, function ($mock) {
                $mock->shouldReceive("getPersonaName")->andReturn("♋ SeeK");
                $mock->shouldReceive("getRealName")->andReturn(null);
            })
        );

        $state = "teststate";
        $this->session->set("steam_state", $state);
        $this->mockSuccessfulAuthentication();

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $user = $this->userRepository->findBySteamId(self::STEAM_ID_64);
        $this->assertNotNull($user);
        $this->assertSame("SeeK", $user->getUsername());
    }

    /** @test */
    public function fills_names_from_steam_real_name()
    {
        // given Steam returns a public real name
        $this->app->instance(
            SteamProfile::class,
            Mockery::mock(SteamProfile::class, function ($mock) {
                $mock->shouldReceive("getPersonaName")->andReturn("SeeK");
                $mock->shouldReceive("getRealName")->andReturn("Jan Kowalski");
            })
        );

        $state = "teststate";
        $this->session->set("steam_state", $state);
        $this->mockSuccessfulAuthentication();

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $user = $this->userRepository->findBySteamId(self::STEAM_ID_64);
        $this->assertNotNull($user);
        $this->assertSame("Jan", $user->getForename());
        $this->assertSame("Kowalski", $user->getSurname());
    }

    /** @test */
    public function rejects_unverified_steam_id()
    {
        // given a Steam ID that was only typed in (not proven)
        $this->factory->user(["steam_id" => $this->legacySteamId()]);

        $state = "teststate";
        $this->session->set("steam_state", $state);
        $this->mockSuccessfulAuthentication();

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/page/login", $response->headers->get("Location"));
        $this->assertNull($this->session->get("uid"));
        $this->assertSame("steam_account_exists", $this->session->get("steam_error"));
    }

    /** @test */
    public function creates_passwordless_account_for_new_steam_user()
    {
        // given
        $state = "teststate";
        $this->session->set("steam_state", $state);
        $this->mockSuccessfulAuthentication();

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/page/profile", $response->headers->get("Location"));

        $user = $this->userRepository->findBySteamId(self::STEAM_ID_64);
        $this->assertNotNull($user);
        $this->assertTrue($user->isSteamVerified());
        $this->assertFalse($user->hasPassword());
        $this->assertTrue(is_steam_placeholder_email($user->getEmail()));
        $this->assertSame("", $user->getContactEmail());
        $this->assertSame($user->getId(), $this->session->get("uid"));
    }

    /** @test */
    public function rejects_callback_with_wrong_state()
    {
        // given
        $this->session->set("steam_state", "correct");
        $this->mockSuccessfulAuthentication();

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams("wrong"));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/page/login", $response->headers->get("Location"));
        $this->assertNull($this->session->get("uid"));
        $this->assertSame("steam_auth_error", $this->session->get("steam_error"));
    }

    /** @test */
    public function rejects_callback_when_authentication_fails()
    {
        // given
        $state = "teststate";
        $this->session->set("steam_state", $state);
        $this->requesterMock
            ->shouldReceive("post")
            ->andReturn(
                new RequestingResponse(200, "ns:http://specs.openid.net/auth/2.0\nis_valid:false\n")
            );

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/page/login", $response->headers->get("Location"));
        $this->assertNull($this->session->get("uid"));
        $this->assertSame("steam_auth_error", $this->session->get("steam_error"));
    }

    /** @test */
    public function can_link_steam_to_existing_account()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        $linkResponse = $this->get("/api/steam/link");
        $this->assertSame(302, $linkResponse->getStatusCode());

        $state = (string) $this->session->get("steam_state");
        $this->mockSuccessfulAuthentication();

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/page/profile", $response->headers->get("Location"));

        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame($this->legacySteamId(), $freshUser->getSteamId());
        $this->assertTrue($freshUser->isSteamVerified());
    }

    /** @test */
    public function cannot_link_steam_occupied_by_another_account()
    {
        // given
        $other = $this->factory->user();
        $this->userRepository->linkSteamId($other->getId(), $this->legacySteamId());

        $user = $this->factory->user();
        $this->actingAs($user);

        $this->get("/api/steam/link");
        $state = (string) $this->session->get("steam_state");
        $this->mockSuccessfulAuthentication();

        // when
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/page/profile", $response->headers->get("Location"));
        $this->assertSame("steam_id_occupied", $this->session->get("steam_error"));
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertNull($freshUser->getSteamId());
    }

    /** @test */
    public function disconnect_clears_steam_id()
    {
        // given
        $user = $this->factory->user(["password" => "abc123"]);
        $this->userRepository->linkSteamId($user->getId(), $this->legacySteamId());
        $this->actingAs($this->userRepository->get($user->getId()));

        // when
        $response = $this->post("/api/steam/disconnect");

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/page/profile", $response->headers->get("Location"));
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertNull($freshUser->getSteamId());
        $this->assertFalse($freshUser->isSteamVerified());
    }

    /** @test */
    public function link_takes_steam_id_over_from_unverified_account()
    {
        // given: someone typed this Steam ID without proving ownership
        $squatter = $this->factory->user(["steam_id" => $this->legacySteamId()]);

        $user = $this->factory->user();
        $this->actingAs($user);
        $this->get("/api/steam/link");
        $state = (string) $this->session->get("steam_state");
        $this->mockSuccessfulAuthentication();

        // when: the real owner connects Steam via OpenID
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then: the verified owner wins, the unverified claim is released
        $this->assertSame(302, $response->getStatusCode());
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame($this->legacySteamId(), $freshUser->getSteamId());
        $this->assertTrue($freshUser->isSteamVerified());

        $freshSquatter = $this->userRepository->get($squatter->getId());
        $this->assertNull($freshSquatter->getSteamId());
        $this->assertFalse($freshSquatter->isSteamVerified());
    }

    /** @test */
    public function cannot_disconnect_steam_only_account()
    {
        // given
        $user = $this->userRepository->createSteamUser(
            "steamuser",
            $this->legacySteamId(),
            "steam-" . self::STEAM_ID_64 . "@steam.invalid",
            "127.0.0.1"
        );
        $this->actingAs($user);

        // when
        $response = $this->post("/api/steam/disconnect");

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("http://localhost/page/profile", $response->headers->get("Location"));
        $this->assertSame("steam_unlink_set_password", $this->session->get("steam_error"));
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame($this->legacySteamId(), $freshUser->getSteamId());
    }

    /** @test */
    public function cannot_replace_an_existing_verified_steam_id()
    {
        // given an account already verified with a different Steam ID
        $user = $this->factory->user();
        $this->userRepository->linkSteamId($user->getId(), "STEAM_0:0:12345678");
        $this->actingAs($this->userRepository->get($user->getId()));

        $this->get("/api/steam/link");
        $state = (string) $this->session->get("steam_state");
        $this->mockSuccessfulAuthentication();

        // when another Steam account is connected
        $response = $this->get("/api/steam/callback", $this->callbackParams($state));

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("steam_already_linked", $this->session->get("steam_error"));
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame("STEAM_0:0:12345678", $freshUser->getSteamId());
    }

    private function callbackParams(string $state): array
    {
        $claimedId = "https://steamcommunity.com/openid/id/" . self::STEAM_ID_64;

        return [
            "state" => $state,
            "openid.ns" => "http://specs.openid.net/auth/2.0",
            "openid.mode" => "id_res",
            "openid.return_to" => "http://localhost/api/steam/callback?state={$state}",
            "openid.claimed_id" => $claimedId,
            "openid.identity" => $claimedId,
        ];
    }

    private function mockSuccessfulAuthentication(): void
    {
        $this->requesterMock
            ->shouldReceive("post")
            ->andReturn(
                new RequestingResponse(200, "ns:http://specs.openid.net/auth/2.0\nis_valid:true\n")
            );
    }

    private function legacySteamId(): string
    {
        return (new SteamIDConverter())->toSteamID(self::STEAM_ID_64);
    }
}
