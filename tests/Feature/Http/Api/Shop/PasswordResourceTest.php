<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Repositories\UserRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class PasswordResourceTest extends HttpTestCase
{
    /** @var UserRepository */
    private $userRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->userRepository = $this->app->make(UserRepository::class);
    }

    /** @test */
    public function updates_password()
    {
        // given
        $user = $this->factory->user([
            'password' => 'prevpass',
        ]);
        $this->actingAs($user);

        // when
        $response = $this->put("/api/password", [
            'old_pass' => 'prevpass',
            'pass' => 'abc123',
            'pass_repeat' => 'abc123',
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
    public function fails_with_invalid_data_passed()
    {
        // given
        $this->actingAs($this->factory->user());

        // when
        $response = $this->put("/api/password", [
            'old_pass' => 'asdsf',
            'pass' => 'ab',
            'pass_repeat' => 'ab',
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "old_pass" =>
                    '<ul class="form_warning help is-danger"><li >Stare hasło jest nieprawidłowe.</li></ul>',
                "pass" =>
                    '<ul class="form_warning help is-danger"><li >Pole musi się składać z co najmniej 6 znaków.</li></ul>',
            ],
            $json["warnings"]
        );
    }
}
