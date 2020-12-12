<?php
namespace Tests\Feature\Http\View\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class GroupsTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/admin/groups");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString('<div class="title is-4">Grupy', $response->getContent());
    }
}
