<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\UserRepository;
use App\Support\Mailer;
use Mockery\MockInterface;
use Tests\Psr4\TestCases\HttpTestCase;

class PasswordForgottenControllerTest extends HttpTestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->make(UserRepository::class);
    }

    /** @test */
    public function send_email_with_forgotten_password_by_email()
    {
        // given
        /** @var MockInterface $mailer */
        $mailer = $this->app->make(Mailer::class);
        $mailer
            ->shouldReceive("send")
            ->once()
            ->andReturn("sent");

        $user = $this->factory->user();

        // when
        $response = $this->post("/api/password/forgotten", [
            "email" => $user->getEmail(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("sent", $json["return_id"]);
    }

    /** @test */
    public function send_email_with_forgotten_password_by_username()
    {
        // given
        /** @var MockInterface $mailer */
        $mailer = $this->app->make(Mailer::class);
        $mailer
            ->shouldReceive("send")
            ->once()
            ->andReturn("sent");

        $user = $this->factory->user();

        // when
        $response = $this->post("/api/password/forgotten", [
            "username" => $user->getUsername(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("sent", $json["return_id"]);
    }

    /** @test */
    public function does_not_send_to_the_steam_placeholder()
    {
        // given a Steam-created account (placeholder address)
        $user = $this->userRepository->createSteamUser(
            "steam_forgot",
            "STEAM_0:0:10000004",
            "steam-forgot@steam.invalid",
            "127.0.0.1"
        );

        // when
        $response = $this->post("/api/password/forgotten", [
            "username" => $user->getUsername(),
        ]);

        // then: generic success, but no reset key was created (nothing was sent)
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("sent", $json["return_id"]);
        $freshUser = $this->userRepository->get($user->getId());
        $this->assertSame("", $freshUser->getResetPasswordKey());
    }

    /** @test */
    public function fails_with_invalid_data()
    {
        // when
        $response = $this->post("/api/password/forgotten", [
            "email" => "asd",
            "username" => "asd",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
    }
}
