<?php
namespace Tests\Feature\Http\Api\Shop;

use Symfony\Component\HttpFoundation\Session\Session;
use Tests\Psr4\TestCases\HttpTestCase;

class LogoutControllerTest extends HttpTestCase
{
    /** @test */
    public function can_log_out()
    {
        // given
        /** @var Session $session */
        $session = $this->app->make(Session::class);
        $this->actingAs($this->factory->user());

        // when
        $response = $this->post("/api/logout");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertArraySubset(
            [
                "return_id" => "logged_out",
            ],
            $json
        );
        $this->assertNull($session->get("uid"));
    }
}
