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
