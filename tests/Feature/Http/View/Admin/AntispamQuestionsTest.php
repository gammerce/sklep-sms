<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\Concerns\AuthConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class AntispamQuestionsTest extends HttpTestCase
{
    use AuthConcern;

    /** @test */
    public function it_loads()
    {
        // given
        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->get('/admin.php', ['pid' => 'antispam_questions']);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
        $this->assertContains(
            '<div class="title is-4">Pytania antyspamowe',
            $response->getContent()
        );
    }
}
