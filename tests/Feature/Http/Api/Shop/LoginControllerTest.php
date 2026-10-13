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
        $this->assertSame(200, $response->getStatusCode());
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
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("not_logged", $json["return_id"]);
    }

    /** @test */
    public function fails_if_data_not_provided()
    {
        // when
        $response = $this->post("/api/login");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_data", $json["return_id"]);
    }

    /**
     * Login must be case-insensitive (long-standing production behavior): ss_users.username
     * and ss_users.email are *_ci columns. Guards against regressions like
     * the blanket utf8mb4_bin conversion, which silently made logins
     * case-sensitive.
     *
     * @test
     * @dataProvider caseVariantProvider
     */
    public function can_login_ignoring_username_case(string $typedLogin)
    {
        // given
        $this->factory->user([
            "username" => "MiXeD_CaSe_User",
            "email" => "Mixed.Case.User@example.com",
            "password" => "abc123",
        ]);

        // when
        $response = $this->post("/api/login", [
            "username" => $typedLogin,
            "password" => "abc123",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("logged_in", $json["return_id"]);
    }

    public static function caseVariantProvider(): array
    {
        return [
            "lowercase username" => ["mixed_case_user"],
            "uppercase username" => ["MIXED_CASE_USER"],
            "lowercase email" => ["mixed.case.user@example.com"],
            "uppercase email" => ["MIXED.CASE.USER@EXAMPLE.COM"],
        ];
    }
}
