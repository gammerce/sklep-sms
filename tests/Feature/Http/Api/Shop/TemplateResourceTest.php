<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class TemplateResourceTest extends HttpTestCase
{
    /** @test */
    public function get_template()
    {
        // when
        $response = $this->get("/api/templates/forgotten_password_sent");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertArrayHasKey("template", $json);
    }
}
