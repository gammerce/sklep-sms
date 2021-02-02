<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\PaymentPlatform;
use Tests\Psr4\TestCases\HttpTestCase;

class PagePaymentPlatformsActionBoxEditTest extends HttpTestCase
{
    private PaymentPlatform $paymentPlatform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentPlatform = $this->factory->paymentPlatform();
    }

    /** @test */
    public function get_edit_box()
    {
        // give
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/api/admin/pages/payment_platforms/action_boxes/edit", [
            "id" => $this->paymentPlatform->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("ok", $json["return_id"]);
        $this->assertStringContainsString("Edytuj platformę płatności", $json["template"]);
    }

    /** @test */
    public function requires_permission_to_get()
    {
        // give
        $this->actingAs($this->factory->user());

        // when
        $response = $this->getJson("/api/admin/pages/payment_platforms/action_boxes/edit", [
            "id" => $this->paymentPlatform->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertEquals("no_access", $json["return_id"]);
    }
}
