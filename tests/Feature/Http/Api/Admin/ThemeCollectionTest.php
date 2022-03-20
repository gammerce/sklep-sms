<?php
namespace Tests\Feature\Http\Api\Admin;

use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class ThemeCollectionTest extends HttpTestCase
{
    /** @test */
    public function list_themes()
    {
        // given
        $this->actingAs(
            $this->factory->privilegedUser([Permission::ACP(), Permission::SETTINGS_MANAGEMENT()])
        );
        $this->factory->template(["theme" => "foo"]);
        $this->factory->template(["theme" => "bar"]);

        // when
        $response = $this->get("/api/admin/themes");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "data" => [["name" => "bar"], ["name" => "foo"]],
            ],
            $json
        );
    }
}
