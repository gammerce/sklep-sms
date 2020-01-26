<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServicesTest extends HttpTestCase
{
    use AuthConcern;
    use MakePurchaseConcern;

    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());
        $this->createRandomPurchase();

        // when
        $response = $this->get('/admin/user_service', ['subpage' => 'extra_flags']);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
        $this->assertContains(
            '<div class="title is-4">Czasowe usługi użytkowników: Dodatkowe flagi',
            $response->getContent()
        );
    }
}
