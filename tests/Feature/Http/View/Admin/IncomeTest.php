<?php
namespace Tests\Feature\Http\View\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class IncomeTest extends HttpTestCase
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
        $response = $this->get("/admin/income");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString("PA: Przychód - Sklep SMS", $response->getContent());
        $this->assertStringContainsString('<tbody class="summary">', $response->getContent());
    }
}
