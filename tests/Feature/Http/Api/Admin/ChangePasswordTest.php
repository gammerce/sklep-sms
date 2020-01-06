<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\UserRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class ChangePasswordTest extends HttpTestCase
{
    /** @test */
    public function changes_a_user_password()
    {
        // given
        /** @var UserRepository $userRepository */
        $userRepository = $this->app->make(UserRepository::class);

        $newPassword = "foobar";
        $admin = $this->factory->admin();
        $user = $this->factory->user();

        $this->actingAs($admin);

        // when
        $response = $this->put("/api/admin/users/{$user->getUid()}/password", [
            "password" => $newPassword,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);

        $freshUser = $userRepository->findByPassword($user->getUsername(), $newPassword);
        $this->assertNotNull($freshUser);
        $this->assertEquals($user->getUid(), $freshUser->getUid());
    }

    /** @test */
    public function could_not_change_password_if_not_authorized()
    {
        // given
        /** @var UserRepository $userRepository */
        $userRepository = $this->app->make(UserRepository::class);

        $password = "foobar";
        $user = $this->factory->user();

        // when
        $response = $this->put("/api/admin/users/{$user->getUid()}/password", [
            "password" => $password,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);

        $freshUser = $userRepository->findByPassword($user->getUsername(), $password);
        $this->assertNull($freshUser);
    }
}
