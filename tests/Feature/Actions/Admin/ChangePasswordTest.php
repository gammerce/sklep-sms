<?php
namespace Tests\Feature\Actions\Admin;

use App\Models\User;
use Tests\Psr4\TestCases\IndexTestCase;

class ChangePasswordTest extends IndexTestCase
{
    /** @test */
    public function changes_a_user_password()
    {
        // given
        $newPassword = "foobar";
        $admin = $this->factory->user(["groups" => 2]);
        $user = $this->factory->user();

        $this->actAs($admin);

        // when
        $response = $this->put("/api/admin/users/{$user->getUid()}/password", [
            "password" => $newPassword,
        ]);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);

        $freshUser = new User(0, $user->getUsername(), $newPassword);
        $this->assertNotNull($freshUser->getUid());
        $this->assertEquals($user->getUid(), $freshUser->getUid());
    }

    /** @test */
    public function could_not_change_password_if_not_authorized()
    {
        // given
        $password = "foobar";
        $user = $this->factory->user();

        // when
        $response = $this->put("/api/admin/users/{$user->getUid()}/password", [
            "password" => $password,
        ]);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);

        $freshUser = new User(0, $user->getUsername(), $password);
        $this->assertNull($freshUser->getUid());
    }
}
