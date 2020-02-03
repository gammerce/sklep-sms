<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class BrickResourceTest extends HttpTestCase
{
    /** @test */
    public function loads_bricks()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->get(
            "/api/bricks/content,logged_info,services_buttons,user_buttons,wallet"
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertContains("Witaj w sklepie internetowym!", $json["content"]["content"]);
        $this->assertContains($user->getUsername(), $json["logged_info"]["content"]);
        $this->assertContains("VIP PRO", $json["services_buttons"]["content"]);
        $this->assertContains("Doładowanie Portfela", $json["user_buttons"]["content"]);
        $this->assertContains("0.00 PLN", $json["wallet"]["content"]);
    }
}
