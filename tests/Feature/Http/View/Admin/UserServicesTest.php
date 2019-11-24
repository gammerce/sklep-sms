<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\TestCases\IndexTestCase;

class UserServicesTest extends IndexTestCase
{
    use AuthConcern;

    /** @test */
    public function it_loads()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->get('/admin.php', ['pid' => 'user_service', 'subpage' => 'extra_flags']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
        $this->assertContains(
            '<div class="title is-4">Czasowe usługi użytkowników: Dodatkowe flagi',
            $response->getContent()
        );
    }
}
