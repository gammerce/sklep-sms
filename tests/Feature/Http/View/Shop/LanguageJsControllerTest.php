<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class LanguageJsControllerTest extends HttpTestCase
{
    /** @test */
    public function generates_lang_js()
    {
        // when
        $response = $this->get("/lang.js");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringStartsWith("window.lang = {", $response->getContent());
    }
}
