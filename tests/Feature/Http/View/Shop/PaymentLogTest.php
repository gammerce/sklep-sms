<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentLogTest extends HttpTestCase
{
    use MakePurchaseConcern;

    /** @test */
    public function is_loads()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);
        $this->createRandomExtraFlagsPurchase(compact("user"));

        // when
        $response = $this->get("/page/payment_log");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Historia płatności", $response->getContent());
    }

    /** @test */
    public function requires_being_logged()
    {
        // when
        $response = $this->get("/page/payment_log");

        // then
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringEndsWith("/login", $response->headers->get("Location"));
    }
}
