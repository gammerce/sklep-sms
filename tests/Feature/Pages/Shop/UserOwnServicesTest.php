<?php
namespace Tests\Feature\Pages\Shop;

use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\TestCases\IndexTestCase;

class UserOwnServicesTest extends IndexTestCase
{
    use AuthConcern;

    /** @test */
    public function it_loads()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->get('/', ['pid' => 'user_own_services']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Moje obecne usługi', $response->getContent());
    }

    /** @test */
    public function requires_being_logged()
    {
        // given

        // when
        $response = $this->get('/', ['pid' => 'user_own_services']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(
            'Nie możesz przeglądać tej strony. Nie jesteś zalogowany/a.',
            $response->getContent()
        );
    }
}
