<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Repositories\UserRepository;
use App\Support\Database;
use Tests\Psr4\TestCases\HttpTestCase;

class PasswordResetControllerTest extends HttpTestCase
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
    public function resets_password()
    {
        // given
        $user = $this->factory->user([
            "password" => "prevpass",
        ]);
        $resetKey = $this->userRepository->createResetPasswordKey($user->getId());

        // when
        $response = $this->post("/api/password/reset", [
            "code" => $resetKey,
            "pass" => "abc123",
            "pass_repeat" => "abc123",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("password_changed", $json["return_id"]);
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame(
            hash_password("abc123", $freshUser->getSalt()),
            $freshUser->getPassword()
        );
    }

    /** @test */
    public function fails_with_invalid_code()
    {
        // when
        $response = $this->post("/api/password/reset", [
            "code" => "asdsf",
            "pass" => "abc123",
            "pass_repeat" => "abc123",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("wrong_sign", $json["return_id"]);
    }

    /** @test */
    public function cannot_be_logged_in()
    {
        $this->actingAs($this->factory->user());

        // when
        $response = $this->post("/api/password/reset");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("logged_in", $json["return_id"]);
    }

    /** @test */
    public function cannot_reset_password_for_admin_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $resetKey = $this->userRepository->createResetPasswordKey(1);

        $response = $this->post("/api/password/reset", [
            "code" => $resetKey,
            "pass" => "abc123",
            "pass_repeat" => "abc123",
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("demo_mode", $json["return_id"]);
    }

    /** @test */
    public function can_reset_password_in_demo_mode()
    {
        putenv("APP_SUBDOMAIN=demo");
        $user = $this->factory->user(["password" => "prevpass"]);
        $resetKey = $this->userRepository->createResetPasswordKey($user->getId());

        $response = $this->post("/api/password/reset", [
            "code" => $resetKey,
            "pass" => "abc123",
            "pass_repeat" => "abc123",
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("password_changed", $json["return_id"]);
    }
}
