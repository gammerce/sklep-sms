<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceBrickControllerTest extends HttpTestCase
{
    /** @test */
    public function loads_brick()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);
        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            'auth_data' => 'myauth',
            'server_id' => $server->getId(),
            'uid' => $user->getUid(),
        ]);

        // when
        $response = $this->get("/api/user_services/{$userService->getId()}/brick");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("<strong>Nick</strong>: myauth<br />", $response->getContent());
    }
}
