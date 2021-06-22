<?php
namespace Tests\Feature\Http\Api\Admin;

use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class ThemeTemplateCollectionTest extends HttpTestCase
{
    /** @test */
    public function list_theme_templates()
    {
        // given
        $this->actingAs(
            $this->factory->privilegedUser([Permission::ACP(), Permission::MANAGE_SETTINGS()])
        );
        $this->factory->template([
            "theme" => "foo",
            "name" => "shop/pages/contact",
            "content" => "foobar",
        ]);

        // when
        $response = $this->get("/api/admin/themes/foo/templates");

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
