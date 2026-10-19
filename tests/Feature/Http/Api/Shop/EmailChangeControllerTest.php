<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Repositories\UserRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class EmailChangeControllerTest extends HttpTestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->make(UserRepository::class);
    }

    /** @test */
    public function requests_email_change()
    {
        // given
        $user = $this->factory->user(["email" => "old@example.com", "password" => "abc123"]);
        $this->actingAs($user);

        // when
        $response = $this->post("/api/profile/email/change", [
            "email" => "new@example.com",
            "password" => "abc123",
        ]);

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString(
            "email_status=email_change_sent",
            (string) $response->headers->get("Location")
        );

        // the address is not changed until it is confirmed
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame("old@example.com", $freshUser->getEmail());
        $this->assertSame(
            "new@example.com",
            $this->userRepository->getPendingEmail($user->getId())
        );
    }

    /** @test */
    public function requires_current_password_for_password_accounts()
    {
        // given
        $user = $this->factory->user(["email" => "old@example.com", "password" => "abc123"]);
        $this->actingAs($user);

        // when: no password
        $response = $this->post("/api/profile/email/change", ["email" => "new@example.com"]);
        $this->assertStringContainsString(
            "email_status=email_change_wrong_password",
            (string) $response->headers->get("Location")
        );

        // when: wrong password
        $response = $this->post("/api/profile/email/change", [
            "email" => "new@example.com",
            "password" => "nope",
        ]);
        $this->assertStringContainsString(
            "email_status=email_change_wrong_password",
            (string) $response->headers->get("Location")
        );

        // then: correct password succeeds
        $response = $this->post("/api/profile/email/change", [
            "email" => "new@example.com",
            "password" => "abc123",
        ]);
        $this->assertStringContainsString(
            "email_status=email_change_sent",
            (string) $response->headers->get("Location")
        );
    }

    /** @test */
    public function passwordless_account_can_request_without_password()
    {
        // given a Steam-created account (no password)
        $user = $this->userRepository->createSteamUser(
            "steam_only",
            "STEAM_0:0:12345678",
            "steam-only@steam.invalid",
            "127.0.0.1"
        );
        $this->assertFalse($user->hasPassword());
        $this->actingAs($user);

        // when
        $response = $this->post("/api/profile/email/change", ["email" => "new@example.com"]);

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString(
            "email_status=email_change_sent",
            (string) $response->headers->get("Location")
        );
    }

    /** @test */
    public function rejects_null_origin_and_allows_missing_origin()
    {
        // given
        $user = $this->factory->user(["email" => "old@example.com", "password" => "abc123"]);
        $this->actingAs($user);

        // when: sandboxed iframe sends "Origin: null" -> treated as cross-site
        $response = $this->post(
            "/api/profile/email/change",
            ["email" => "a@example.com", "password" => "abc123"],
            [],
            ["Origin" => "null"]
        );

        // then
        $this->assertStringContainsString(
            "email_status=email_change_invalid",
            (string) $response->headers->get("Location")
        );

        // when: no Origin/Referer at all -> allowed
        $response = $this->post("/api/profile/email/change", [
            "email" => "b@example.com",
            "password" => "abc123",
        ]);

        // then
        $this->assertStringContainsString(
            "email_status=email_change_sent",
            (string) $response->headers->get("Location")
        );
    }

    /** @test */
    public function accepts_same_origin_and_rejects_cross_site_posts()
    {
        // given
        $user = $this->factory->user(["email" => "old@example.com", "password" => "abc123"]);
        $this->actingAs($user);

        // when: same-origin request
        $response = $this->post(
            "/api/profile/email/change",
            ["email" => "same@example.com", "password" => "abc123"],
            [],
            ["Origin" => "http://localhost"]
        );

        // then
        $this->assertStringContainsString(
            "email_status=email_change_sent",
            (string) $response->headers->get("Location")
        );

        // when: cross-site request
        $response = $this->post(
            "/api/profile/email/change",
            ["email" => "evil@example.com", "password" => "abc123"],
            [],
            ["Origin" => "https://evil.example"]
        );

        // then
        $this->assertStringContainsString(
            "email_status=email_change_invalid",
            (string) $response->headers->get("Location")
        );
    }

    /** @test */
    public function cannot_request_taken_email()
    {
        // given
        $this->factory->user(["email" => "taken@example.com"]);
        $user = $this->factory->user(["email" => "old@example.com", "password" => "abc123"]);
        $this->actingAs($user);

        // when
        $response = $this->post("/api/profile/email/change", [
            "email" => "taken@example.com",
            "password" => "abc123",
        ]);

        // then
        $this->assertStringContainsString(
            "email_status=email_change_invalid",
            (string) $response->headers->get("Location")
        );
        $this->assertNotSame(
            "taken@example.com",
            $this->userRepository->getPendingEmail($user->getId())
        );
    }

    /** @test */
    public function confirms_email_change()
    {
        // given
        $user = $this->factory->user(["email" => "old@example.com"]);
        $this->userRepository->setEmailChange($user->getId(), "new@example.com", "testkey");

        // when
        $response = $this->post("/api/profile/email/confirm", [
            "code" => "testkey",
        ]);

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString(
            "email_status=email_changed",
            (string) $response->headers->get("Location")
        );
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame("new@example.com", $freshUser->getEmail());
        $this->assertSame("", $this->userRepository->getPendingEmail($user->getId()));
    }

    /** @test */
    public function rejects_wrong_token()
    {
        // given
        $user = $this->factory->user(["email" => "old@example.com"]);

        // when
        $response = $this->post("/api/profile/email/confirm", [
            "code" => "nope",
        ]);

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString(
            "email_status=email_change_wrong_token",
            (string) $response->headers->get("Location")
        );
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame("old@example.com", $freshUser->getEmail());
    }

    /** @test */
    public function rejects_email_taken_before_confirmation()
    {
        // given
        $user = $this->factory->user(["email" => "old@example.com"]);
        $this->userRepository->setEmailChange($user->getId(), "taken@example.com", "testkey");
        $this->factory->user(["email" => "taken@example.com"]);

        // when
        $response = $this->post("/api/profile/email/confirm", [
            "code" => "testkey",
        ]);

        // then
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString(
            "email_status=email_occupied",
            (string) $response->headers->get("Location")
        );
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame("old@example.com", $freshUser->getEmail());
    }
}
