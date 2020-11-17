<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentSuccessTest extends HttpTestCase
{
    /** @test */
    public function is_loads()
    {
        // when
        $response = $this->get("/page/payment_success");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Płatność zaakceptowana", $response->getContent());
    }
}
