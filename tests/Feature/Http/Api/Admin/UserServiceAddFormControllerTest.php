<?php
namespace Tests\Feature\Http\Api\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServiceAddFormControllerTest extends HttpTestCase
{
    /** @test */
    public function get_add_form()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->getJson("/api/admin/services/vippro/user_services/add_form");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("data-module=\"extra_flags\"", $response->getContent());
    }
}
