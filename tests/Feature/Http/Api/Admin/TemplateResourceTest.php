<?php
namespace Tests\Feature\Http\Api\Admin;

use App\User\Permission;
use Tests\Psr4\TestCases\HttpTestCase;

class TemplateResourceTest extends HttpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            $this->factory->privilegedUser([Permission::ACP(), Permission::SETTINGS_MANAGEMENT()])
        );
    }

    /** @test */
    public function get_template()
    {
        // when
        $response = $this->get("/api/admin/templates/shop-pages-contact");

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
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "lang" => null,
            "content" => "baz",
        ]);

        // when
        $response = $this->get("/api/admin/templates/shop-pages-contact", ["theme" => "foo"]);

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
    public function get_overridden_i18n_template()
    {
        // given
        $this->factory->template([
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "lang" => "pl",
            "content" => "baz",
        ]);

        // when
        $response = $this->get("/api/admin/templates/shop-pages-contact", [
            "theme" => "foo",
            "lang" => "pl",
        ]);

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
        $response = $this->get("/api/admin/templates/example");

        // then
        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function fails_on_getting_unsupported_lang()
    {
        // when
        $response = $this->get("/api/admin/templates/shop-pages-contact", ["lang" => "us"]);

        // then
        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function create_template()
    {
        // when
        $response = $this->put(
            "/api/admin/templates/shop-pages-contact",
            [
                "content" => "bar",
            ],
            [
                "theme" => "foo",
            ]
        );

        // then
        $this->assertSame(204, $response->getStatusCode());
        $this->assertDatabaseHas("ss_templates", [
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "content" => "bar",
        ]);
    }

    /** @test */
    public function create_i18n_template()
    {
        // when
        $response = $this->put(
            "/api/admin/templates/shop-pages-contact",
            [
                "content" => "bar",
            ],
            [
                "theme" => "foo",
                "lang" => "pl",
            ]
        );

        // then
        $this->assertSame(204, $response->getStatusCode());
        $this->assertDatabaseHas("ss_templates", [
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "lang" => "pl",
            "content" => "bar",
        ]);
    }

    /** @test */
    public function update_template()
    {
        // given
        $this->factory->template([
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "content" => "quy",
        ]);

        // when
        $response = $this->put(
            "/api/admin/templates/shop-pages-contact",
            [
                "content" => "bar",
            ],
            [
                "theme" => "foo",
            ]
        );

        // then
        $this->assertSame(204, $response->getStatusCode());
        $this->assertDatabaseHas("ss_templates", [
            "theme" => "foo",
            "name" => "shop/pages/contact",
            "content" => "bar",
        ]);
    }

    /** @test */
    public function update_i18n_template()
    {
        // given
        $this->factory->template([
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "lang" => "en",
            "content" => "quy",
        ]);

        // when
        $response = $this->put(
            "/api/admin/templates/shop-pages-contact",
            [
                "content" => "bar",
            ],
            [
                "theme" => "foo",
                "lang" => "en",
            ]
        );

        // then
        $this->assertSame(204, $response->getStatusCode());
        $this->assertDatabaseHas("ss_templates", [
            "name" => "shop/pages/contact",
            "theme" => "foo",
            "lang" => "en",
            "content" => "bar",
        ]);
    }
}
