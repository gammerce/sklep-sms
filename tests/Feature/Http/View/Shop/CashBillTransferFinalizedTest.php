<?php
namespace Tests\Feature\Http\View\Shop;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class CashBillTransferFinalizedTest extends HttpTestCase
{
    use MakePurchaseConcern;

    /** @test */
    public function is_loads()
    {
        // when
        $response = $this->get("/page/transfer_finalized", [
            "status" => "OK",
            "orderid" => "example",
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString(
            "Twoja płatność zakończyła się pomyślnie",
            $response->getContent()
        );
    }

    /** @test */
    public function is_shows_error_on_invalid_status()
    {
        // when
        $response = $this->get("/page/transfer_finalized", [
            "status" => "error",
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("zakończyła się niepowodzeniem", $response->getContent());
    }
}
