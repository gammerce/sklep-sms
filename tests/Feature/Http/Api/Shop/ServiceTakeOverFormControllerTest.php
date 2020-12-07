<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class ServiceTakeOverFormControllerTest extends HttpTestCase
{
    /** @test */
    public function loads_brick()
    {
        // given
        $this->actingAs($this->factory->user());

        // when
        $response = $this->get("/api/services/vip/take_over/create_form");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString("services/extra_flags/service_take_over", $response->getContent());
    }
}
