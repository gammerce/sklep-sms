<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PaymentPlatformsTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());
        $this->factory->paymentPlatform();

        // when
        $response = $this->get("/admin/payment_platforms");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("Panel Admina", $response->getContent());
        $this->assertContains(
            '<div class="title is-4">Platformy płatności',
            $response->getContent()
        );
    }
}
