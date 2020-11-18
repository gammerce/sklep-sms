<?php
namespace Tests\Feature\Http\View\Admin;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class SmsCodesTest extends HttpTestCase
{
    /** @test */
    public function it_loads()
    {
        // given
        $this->actingAs($this->factory->admin());

        $this->factory->smsCode([
            "free" => false,
        ]);
        $this->factory->smsCode([
            "sms_price" => 500,
            "free" => true,
        ]);

        // when
        $response = $this->get("/admin/sms_codes");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Panel Admina", $response->getContent());
        $this->assertContains('<div class="title is-4">Darmowe kody SMS', $response->getContent());
    }

    /** @test */
    public function it_loads_paginated()
    {
        // given
        $this->actingAs($this->factory->admin());

        for ($i = 0; $i < 40; ++$i) {
            $this->factory->smsCode([
                "sms_price" => 500,
                "free" => true,
            ]);
        }

        // when
        $response = $this->get("/admin/sms_codes");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Panel Admina", $response->getContent());
        $this->assertContains('<div class="title is-4">Darmowe kody SMS', $response->getContent());
    }
}
