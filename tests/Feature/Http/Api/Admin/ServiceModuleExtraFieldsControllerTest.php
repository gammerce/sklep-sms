<?php
namespace Tests\Feature\Http\Api\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ServiceModuleExtraFieldsControllerTest extends HttpTestCase
{
    /** @test */
    public function get_extra_fields()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/api/admin/services/vip/modules/extra_flags/extra_fields");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("data-module=\"extra_flags\"", $response->getContent());
    }
}
