<?php
namespace Tests\Feature\Http\Api\Admin;

use App\ServiceModules\ExtraFlags\ExtraFlagUserService;
use Tests\Psr4\TestCases\HttpTestCase;

class PageUserServiceActionBoxEditTest extends HttpTestCase
{
    /** @var ExtraFlagUserService */
    private $userService;

    protected function setUp()
    {
        parent::setUp();
        $this->userService = $this->factory->extraFlagUserService();
    }

    /** @test */
    public function get_edit_box()
    {
        // give
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->getJson("/api/admin/pages/user_service/action_boxes/edit", [
            "id" => $this->userService->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertContains("Edytuj usługę użytkownika", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/user_service/action_boxes/edit", [
            "id" => $this->userService->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
