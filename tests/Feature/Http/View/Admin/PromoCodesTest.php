<?php
namespace Tests\Feature\Http\View\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PromoCodesTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());
        $this->factory->promoCode();
        $this->factory->promoCode();

        // when
        $response = $this->get("/admin/promo_codes");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Panel Admina", $response->getContent());
        $this->assertContains("<div class=\"title is-4\">Kody promocyjne", $response->getContent());
    }
}
