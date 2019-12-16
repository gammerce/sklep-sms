<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Tests\Psr4\TestCases\HttpTestCase;

class RegisterTest extends HttpTestCase
{
    /** @test */
    public function can_register()
    {
        // given
        /** @var Session $session */
        $session = $this->app->make(Session::class);

        /** @var UserRepository $userRepository */
        $userRepository = $this->app->make(UserRepository::class);

        $email = 'abc123@example.com';
        $password = 'abc123';
        $username = 'janek';
        $forename = 'Jan';
        $surname = 'Nowak';
        $steamId = 'STEAM_1:0:22309350';

        $session->setName("user");
        $session->start();
        $session->set("asid", 1);

        // when
        $response = $this->post('/api/register', [
            'username' => $username,
            'password' => $password,
            'password_repeat' => $password,
            'email' => $email,
            'email_repeat' => $email,
            'forename' => $forename,
            'surname' => $surname,
            'steam_id' => $steamId,
            'as_id' => 1,
            'as_answer' => 'e',
        ]);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $user = $userRepository->findByPassword($username, $password);
        $this->assertNotNull($user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($forename, $user->getForename());
        $this->assertEquals($surname, $user->getSurname());
        $this->assertEquals($steamId, $user->getSteamId());
        $this->assertNotNull($user->getRegDate());
    }
}
