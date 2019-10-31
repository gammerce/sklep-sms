<?php
namespace Tests\Feature\Actions\Shop;

use App\Models\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Tests\Psr4\TestCases\IndexTestCase;

class RegisterTest extends IndexTestCase
{
    /** @test */
    public function can_register()
    {
        // given
        $email = 'abc123@example.com';
        $password = 'abc123';
        $username = 'janek';
        $forename = 'Jan';
        $surname = 'Nowak';
        $steamId = 'STEAM_1:0:22309350';

        /** @var Session $session */
        $session = $this->app->make(Session::class);
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
        $user = new User(0, $username, $password);
        $this->assertNotNull($user->getUid());
        $this->assertEquals($email, $user->getEmail(false));
        $this->assertEquals($forename, $user->getForename());
        $this->assertEquals($surname, $user->getSurname());
        $this->assertEquals($steamId, $user->getSteamId());
        $this->assertNotNull($user->getRegDate());
    }
}
