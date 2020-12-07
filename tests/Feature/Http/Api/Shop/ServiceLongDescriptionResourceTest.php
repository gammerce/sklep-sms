<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class ServiceLongDescriptionResourceTest extends HttpTestCase
{
    /** @test */
    public function get_service_long_description()
    {
        // when
        $response = $this->get("/api/services/vip/long_description");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString("VIP", $response->getContent());
    }
}
