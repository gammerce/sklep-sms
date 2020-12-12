<?php
namespace Tests\Feature\Http\View\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class UsersTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());
        $this->factory->user();

        // when
        $response = $this->get("/admin/users");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString(
            '<div class="title is-4">Użytkownicy',
            $response->getContent()
        );
    }
}
