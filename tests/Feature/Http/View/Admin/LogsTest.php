<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class LogsTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());
        $this->factory->log(["message" => "My example"]);

        // when
        $response = $this->get("/admin/logs", ["search" => "ex"]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("Panel Admina", $response->getContent());
        $this->assertContains("<div class=\"title is-4\">Logi", $response->getContent());
    }
}
