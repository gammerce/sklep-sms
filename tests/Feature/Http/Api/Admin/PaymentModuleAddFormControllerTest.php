<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Verification\PaymentModules\Cssetti;
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
        $response = $this->get("/api/admin/payment_modules/{$paymentModuleId}/add_form");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("ID KONTA", $response->getContent());
    }
}
