<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class LoginControllerTest extends HttpTestCase
{
    /** @test */
    public function can_login()
    {
        // given
        $this->factory->user([
            "username" => "test",
            "password" => "abc123",
        ]);

        // when
        $response = $this->post("/api/login", [
            "username" => "test",
            "password" => "abc123",
        ]);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("logged_in", $json["return_id"]);
    }

    /** @test */
    public function cannot_login_with_invalid_credentials()
    {
        // given
        $this->factory->user([
            "username" => "test",
            "password" => "abc123",
        ]);

        // when
        $response = $this->post("/api/login", [
            "username" => "test",
            "password" => "abc1234",
        ]);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("not_logged", $json["return_id"]);
    }

    /** @test */
    public function fails_if_data_not_provided()
    {
        // when
        $response = $this->post("/api/login");

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_data", $json["return_id"]);
    }
}
