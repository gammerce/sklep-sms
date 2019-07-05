<?php
namespace Tests\Feature\Pages\Admin;

use App\Exceptions\LicenseRequestException;
use App\License;
use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\TestCases\IndexTestCase;

class HomepageTest extends IndexTestCase
{
    use AuthConcern;

    /** @test */
    public function it_loads()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->get('/admin.php');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
        $this->assertContains('<div class="title">Strona główna', $response->getContent());
    }

    /** @test */
    public function user_can_access_acp_if_license_is_invalid()
    {
        // given
        $license = $this->app->make(License::class);
        $license->shouldReceive('isValid')->andReturn(false);
        $license->shouldReceive('getLoadingException')->andReturn(new LicenseRequestException());

        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->get('/admin.php');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
    }

    /** @test */
    public function it_requires_login_when_not_logged()
    {
        // given

        // when
        $response = $this->get('/admin.php');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('PA: Login - Sklep SMS', $response->getContent());
    }
}
