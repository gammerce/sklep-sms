<?php
namespace Tests\Feature\Http\View\Shop;

use Tests\Psr4\TestCases\HttpTestCase;

class LanguageJsControllerTest extends HttpTestCase
{
    /** @test */
    public function generates_lang_js()
    {
        // when
        $response = $this->get("/lang.js");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            "var lang = {\n    ajax_error: \"Wystąpił błąd podczas pozyskiwania danych.\",\n    sth_went_wrong: \"Coś poszło nie tak :/\"\n};\n",
            $response->getContent()
        );
    }
}
