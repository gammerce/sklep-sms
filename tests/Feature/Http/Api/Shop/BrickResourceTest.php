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
            "/api/bricks/content:home,logged_info,services_buttons,user_buttons,wallet"
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertStringContainsString("Witaj w Sklepie SMS!", $json["content"]["content"]);
        $this->assertStringContainsString($user->getUsername(), $json["logged_info"]["content"]);
        $this->assertStringContainsString("VIP PRO", $json["services_buttons"]["content"]);
        $this->assertStringContainsString("Moje usługi", $json["user_buttons"]["content"]);
        $this->assertStringContainsString("0.00", $json["wallet"]["content"]);
    }
}
