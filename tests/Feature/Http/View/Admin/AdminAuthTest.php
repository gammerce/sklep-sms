<?php
namespace Tests\Feature\Http\View\Admin;

use App\System\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class AdminAuthTest extends HttpTestCase
{
    /** @test */
    public function get_login_page()
    {
        // when
        $response = $this->get("/admin/login");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Zaloguj się", $response->getContent());
    }

    /** @test */
    public function login_user()
    {
        // given
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);

        $user = $this->factory->admin([
            "username" => "example",
            "password" => "abc123",
        ]);

        // when
        $response = $this->post("/admin/login", [
            "username" => "example",
            "password" => "abc123",
        ]);

        // then
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith("/admin", $response->headers->get("Location"));
        $this->assertSame($user->getId(), $auth->user()->getId());
    }

    /** @test */
    public function logout_user()
    {
        // given
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);

        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/admin/login", [
            "action" => "logout",
        ]);

        // then
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith("/admin/login", $response->headers->get("Location"));
        $this->assertFalse($auth->check());
    }
}
