<?php
namespace Tests\Feature\Http\View\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class SmsCodesTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());

        for ($i = 0; $i < 10; ++$i) {
            $this->factory->smsCode([
                'free' => false,
            ]);
        }

        for ($i = 0; $i < 16; ++$i) {
            $this->factory->smsCode([
                'sms_price' => 500,
                'free' => true,
            ]);
        }

        // when
        $response = $this->get('/admin/sms_codes');

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Panel Admina', $response->getContent());
        $this->assertContains('<div class="title is-4">Darmowe kody SMS', $response->getContent());
    }
}
