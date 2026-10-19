<?php
namespace Tests\Feature\Repositories;

use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\SteamIDConverter;
use Tests\Psr4\TestCases\HttpTestCase;

class UserRepositorySteamIdTest extends HttpTestCase
{
    private UserRepository $userRepository;
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->make(UserRepository::class);
        $this->db = $this->app->make(Database::class);
    }

    /** @test */
    public function stores_steam_id_canonically()
    {
        // given
        $legacy = "STEAM_1:1:77777777";
        $canonical = (new SteamIDConverter())->toSteamID64($legacy);

        // when
        $user = $this->factory->user(["steam_id" => $legacy]);

        // then the DB holds the canonical SteamID64 ...
        $stored = $this->db
            ->query("SELECT `steam_id` FROM `ss_users` WHERE `uid` = " . (int) $user->getId())
            ->fetchColumn();
        $this->assertSame($canonical, $stored);
        $this->assertMatchesRegularExpression("/^\d{17}$/", $stored);

        // ... while the model exposes the legacy STEAM_0 form.
        $this->assertSame("STEAM_0:1:77777777", $user->getSteamId());
    }

    /** @test */
    public function contact_email_hides_the_placeholder()
    {
        // normal address is returned as-is
        $normal = $this->factory->user(["email" => "real@example.com"]);
        $this->assertSame("real@example.com", $normal->getContactEmail());

        // the Steam placeholder is hidden
        $steam = $this->userRepository->createSteamUser(
            "steamx",
            "STEAM_0:1:10000003",
            "steam-x@steam.invalid",
            "127.0.0.1"
        );
        $this->assertTrue(is_steam_placeholder_email($steam->getEmail()));
        $this->assertSame("", $steam->getContactEmail());
    }

    /** @test */
    public function finds_by_any_steam_id_format()
    {
        // given
        $user = $this->factory->user(["steam_id" => "STEAM_1:1:77777777"]);

        // when / then: universe prefix and format do not matter
        $this->assertSame(
            $user->getId(),
            $this->userRepository->findBySteamId("STEAM_0:1:77777777")->getId()
        );
        $this->assertSame(
            $user->getId(),
            $this->userRepository->findBySteamId("STEAM_1:1:77777777")->getId()
        );
        $this->assertSame(
            $user->getId(),
            $this->userRepository
                ->findBySteamId((new SteamIDConverter())->toSteamID64("STEAM_0:1:77777777"))
                ->getId()
        );
        $this->assertNull($this->userRepository->findBySteamId("not-a-steam-id"));
    }
}
