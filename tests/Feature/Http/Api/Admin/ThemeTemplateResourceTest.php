<?php
namespace Tests\Feature\Http\Api\Admin;

use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class ThemeTemplateResourceTest extends HttpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            $this->factory->privilegedUser([Permission::ACP(), Permission::MANAGE_SETTINGS()])
        );
    }

    /** @test */
    public function get_template()
    {
        // when
        $response = $this->get("/api/admin/themes/foo/templates/shop-pages-contact");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertArraySubset(
            [
                "name" => "shop/pages/contact",
                "content" => <<<EOF
<header class="section">
    <div class="container">
        <h1 class="title">{{ __('contact') }}</h1>
        <h2 class="subtitle">{{ __('contact_info') }}</h2>
    </div>
</header>

<main class="section section-body">
    <div class="container">
        <ul class="link-list">
            {!! \$emailSection !!} {!! \$ggSection !!}
        </ul>
    </div>
</main>

EOF
            ,
            ],
            $json
        );
    }

    /** @test */
    public function get_overridden_template()
    {
        // given
        $this->factory->template([
            "theme" => "foo",
            "name" => "shop/pages/contact",
            "content" => "baz",
        ]);

        // when
        $response = $this->get("/api/admin/themes/foo/templates/shop-pages-contact");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "name" => "shop/pages/contact",
                "content" => "baz",
            ],
            $json
        );
    }

    /** @test */
    public function fails_on_getting_invalid_template()
    {
        // when
        $response = $this->get("/api/admin/themes/foo/templates/example");

        // then
        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function create_template()
    {
        // when
        $response = $this->put("/api/admin/themes/foo/templates/shop-pages-contact", [
            "content" => "bar",
        ]);

        // then
        $this->assertSame(204, $response->getStatusCode());
        $this->assertDatabaseHas("ss_templates", [
            "theme" => "foo",
            "name" => "shop/pages/contact",
            "content" => "bar",
        ]);
    }

    /** @test */
    public function update_template()
    {
        // given
        $this->factory->template([
            "theme" => "foo",
            "name" => "shop/pages/contact",
            "content" => "quy",
        ]);

        // when
        $response = $this->put("/api/admin/themes/foo/templates/shop-pages-contact", [
            "content" => "bar",
        ]);

        // then
        $this->assertSame(204, $response->getStatusCode());
        $this->assertDatabaseHas("ss_templates", [
            "theme" => "foo",
            "name" => "shop/pages/contact",
            "content" => "bar",
        ]);
    }
}
