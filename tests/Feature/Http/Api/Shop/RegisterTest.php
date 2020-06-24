<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Repositories\UserRepository;
use App\Requesting\Response;
use Mockery;
use Tests\Psr4\TestCases\HttpTestCase;

class RegisterTest extends HttpTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->requesterMock->shouldReceive("post")
            ->withArgs([
                "https://license.sklep-sms.pl/v1/captcha",
                [
                    "response" => "example",
                    "remoteip" => "127.0.0.1",
                ],
                Mockery::any()
            ])
            ->andReturn(new Response(200, json_encode(["success" => true])));
    }

    /** @test */
    public function can_register()
    {
        // given
        /** @var UserRepository $userRepository */
        $userRepository = $this->app->make(UserRepository::class);

        $email = "abc123@example.com";
        $password = "abc123";
        $username = "janek";
        $forename = "Jan";
        $surname = "Nowak";
        $steamId = "STEAM_1:0:22309350";

        // when
        $response = $this->post("/api/register", [
            "username" => $username,
            "password" => $password,
            "password_repeat" => $password,
            "email" => $email,
            "email_repeat" => $email,
            "forename" => $forename,
            "surname" => $surname,
            "steam_id" => $steamId,
            "h-captcha-response" => "example",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("registered", $json["return_id"]);
        $user = $userRepository->findByPassword($username, $password);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($forename, $user->getForename());
        $this->assertEquals($surname, $user->getSurname());
        $this->assertEquals($steamId, $user->getSteamId());
        $this->assertNotNull($user->getRegDate());
    }

    /** @test */
    public function cannot_register_twice_using_the_same_email()
    {
        $this->factory->user([
            "email" => "abc123@example.com",
        ]);

        // when
        $response = $this->post("/api/register", [
            "username" => "janek",
            "password" => "abc123",
            "password_repeat" => "abc123",
            "email" => "abc123@example.com",
            "email_repeat" => "abc123@example.com",
            "forename" => "Jan",
            "surname" => "Nowak",
            "steam_id" => "",
            "h-captcha-response" => "example",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "email" => ["Podany e-mail jest już zajęty."],
            ],
            $json["warnings"]
        );
    }
}
