<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class ExtraFlagsTest extends HttpTestCase
{
    use MakePurchaseConcern;

    protected function setUp()
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
        $response = $this->get("/admin/players_flags");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("Panel Admina", $response->getContent());
        $this->assertContains('<div class="title is-4">Flagi graczy', $response->getContent());
    }
}
