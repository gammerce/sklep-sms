<?php
namespace Tests\Feature\Http\View\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class BoughtServicesTest extends HttpTestCase
{
    use MakePurchaseConcern;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRandomExtraFlagsPurchase();
    }

    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/admin/bought_services", ["search" => "a"]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString(
            '<div class="title is-4">Zakupione usługi',
            $response->getContent()
        );
    }
}
