<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceEditFormControllerTest extends HttpTestCase
{
    /** @test */
    public function load_form()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);
        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'server_id' => $server->getId(),
            'uid' => $user->getUid(),
        ]);

        // when
        $response = $this->get("/api/user_services/{$userService->getId()}/edit_form");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains(
            "services/extra_flags/user_own_service_edit",
            $response->getContent()
        );
    }
}
