<?php
namespace Tests\Feature\Pages\Admin;

use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\TestCases\AdminTestCase;

class HomepageTest extends AdminTestCase
{
    use AuthConcern;

    /** @test */
    public function it_loads()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->call('GET', '/');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
        $this->assertContains('<div class="title">Strona główna', $response->getContent());
    }

    /** @test */
    public function it_requires_login_when_not_logged()
    {
        // given

        // when
        $response = $this->call('GET', '/');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('PA: Login - Sklep SMS', $response->getContent());
    }
}