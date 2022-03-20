<?php
namespace Tests\Feature\Http\Api\Admin;

use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class TemplateCollectionTest extends HttpTestCase
{
    /** @test */
    public function list_theme_templates()
    {
        // given
        $this->actingAs(
            $this->factory->privilegedUser([Permission::ACP(), Permission::SETTINGS_MANAGEMENT()])
        );
        $this->factory->template([
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "lang" => "pl",
            "content" => "foobar",
        ]);

        // when
        $response = $this->get("/api/admin/templates", ["lang" => "pl", "theme" => "foo"]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "data" => [
                    [
                        "name" => "shop/pages/contact",
                        "deletable" => true,
                    ],
                    [
                        "name" => "shop/pages/regulations",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/services/goresnick_desc",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/services/goresslot_desc",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/services/govip_desc",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/services/govippro_desc",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/services/resnick_desc",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/services/resslot_desc",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/services/vip_desc",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/services/vippro_desc",
                        "deletable" => false,
                    ],
                    [
                        "name" => "shop/styles/general",
                        "deletable" => false,
                    ],
                ],
            ],
            $json
        );
    }
}
