<?php
namespace Tests\Feature\Http\Api\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class CronControllerTest extends HttpTestCase
{
    /** @test */
    public function runs_cron()
    {
        // given
        $this->factory->extraFlagUserService([
            "server_id" => $this->factory->server()->getId(),
        ]);

        // when
        $response = $this->get("/api/cron");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("OK", $response->getContent());
    }
}
