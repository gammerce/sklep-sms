<?php
namespace Tests\Feature\Http\Api\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class LogResourceTest extends HttpTestCase
{
    /** @test */
    public function deletes_log()
    {
        // given
        $this->actingAs($this->factory->admin());
        $logId = $this->factory->log();

        // when
        $response = $this->delete("/api/admin/logs/{$logId}");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertDatabaseDoesntHave("ss_logs", [
            "id" => $logId,
        ]);
    }
}
