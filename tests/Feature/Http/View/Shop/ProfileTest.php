<?php
namespace Tests\Feature\Http\View\Shop;

use App\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ProfileTest extends HttpTestCase
{
    /** @test */
    public function is_loads()
    {
        // given
        $this->actingAs(
            $this->factory->user([
                "username" => "my_example_username",
            ])
        );

        // when
        $response = $this->get("/page/profile");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Profil", $response->getContent());
        $this->assertStringContainsString("my_example_username", $response->getContent());
    }

    /** @test */
    public function requires_being_logged()
    {
        // when
        $response = $this->get("/page/profile");

        // then
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith("/login", $response->headers->get("Location"));
    }

    /** @test */
    public function email_change_password_is_required_for_password_accounts()
    {
        // given
        $this->actingAs($this->factory->user(["password" => "abc123"]));

        // when
        $input = $this->emailPasswordInput();

        // then
        $this->assertStringContainsString("required", $input);
        $this->assertStringNotContainsString("disabled", $input);
    }

    /** @test */
    public function email_change_password_is_disabled_for_passwordless_accounts()
    {
        // given a Steam-created account (no password)
        $user = $this->app
            ->make(UserRepository::class)
            ->createSteamUser("pwless", "STEAM_0:0:10000001", "pwless@steam.invalid", "127.0.0.1");
        $this->actingAs($user);

        // when
        $input = $this->emailPasswordInput();

        // then
        $this->assertStringContainsString("disabled", $input);
        $this->assertStringNotContainsString("required", $input);
    }

    /** @test */
    public function hides_the_steam_placeholder_email()
    {
        // given a Steam-created account (placeholder address)
        $user = $this->app
            ->make(UserRepository::class)
            ->createSteamUser(
                "pwless2",
                "STEAM_0:0:10000002",
                "steam-x@steam.invalid",
                "127.0.0.1"
            );
        $this->actingAs($user);

        // when
        $content = $this->get("/page/profile")->getContent();

        // then the internal placeholder is not shown, the prompt is
        $this->assertStringNotContainsString("@steam.invalid", $content);
        $this->assertStringContainsString("nie ma jeszcze adresu e-mail", $content);
    }

    private function emailPasswordInput(): string
    {
        $content = $this->get("/page/profile")->getContent();

        if (preg_match('/<input\b[^>]*id="email_change_password"[^>]*>/s', $content, $matches)) {
            return $matches[0];
        }

        return "";
    }
}
