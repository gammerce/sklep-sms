<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class GroupResourceTest extends HttpTestCase
{
    /** @var int */
    private $groupId;

    protected function setUp()
    {
        parent::setUp();

        $this->actingAs($this->factory->admin());

        $createResponse = $this->post("/api/admin/groups", [
            'name' => 'example',
        ]);
        $createResponseJson = $this->decodeJsonResponse($createResponse);
        $this->groupId = $createResponseJson["data"]["id"];
    }

    /** @test */
    public function updates_group()
    {
        // when
        $response = $this->put("/api/admin/groups/{$this->groupId}", [
            'name' => 'example2',
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
    }

    /** @test */
    public function deletes_group()
    {
        // when
        $response = $this->delete("/api/admin/groups/{$this->groupId}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
    }
}
