<?php
namespace Tests\Feature\Http\View\Admin;

use App\User\Permission;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ThemeTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs(
            $this->factory->privilegedUser([Permission::ACP(), Permission::MANAGE_SETTINGS()])
        );

        // when
        $response = $this->get("/admin/theme");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString('<div class="title is-4">Motyw', $response->getContent());
    }
}
