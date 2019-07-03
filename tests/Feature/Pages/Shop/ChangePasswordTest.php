<?php
namespace Tests\Feature\Pages\Shop;

use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\TestCases\IndexTestCase;

class ChangePasswordTest extends IndexTestCase
{
    use AuthConcern;

    /** @test */
    public function it_loads()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->get('/', ['pid' => 'change_password']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Zmiana hasła', $response->getContent());
    }

    /** @test */
    public function requires_being_logged()
    {
        // given

        // when
        $response = $this->get('/', ['pid' => 'change_password']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(
            'Nie możesz przeglądać tej strony. Nie jesteś zalogowany/a.',
            $response->getContent()
        );
    }
}
