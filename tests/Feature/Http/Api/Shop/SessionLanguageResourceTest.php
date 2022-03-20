<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class SessionLanguageResourceTest extends HttpTestCase
{
    /** @test */
    public function can_change_language()
    {
        // when
        $response = $this->put("/api/session/language");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            "language=pl; path=/; samesite=lax",
            $response->headers->get("set-cookie")
        );
    }
}
