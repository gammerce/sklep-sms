<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Verification\PaymentModules\Cssetti;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentModuleAddFormControllerTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());
        $paymentModuleId = Cssetti::MODULE_ID;

        // when
        $response = $this->getJson("/api/admin/payment_modules/{$paymentModuleId}/add_form");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("ID KONTA", $response->getContent());
    }

    /** @test */
    public function returns_404_for_invalid_payment_module()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->getJson("/api/admin/payment_modules/asd/add_form");

        // then
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
