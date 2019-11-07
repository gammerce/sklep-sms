<?php
namespace Tests\Unit;

use App\System\Mailer;
use Tests\Psr4\TestCases\TestCase;

class MailerTest extends TestCase
{
    /** @test */
    public function can_initialize_mailer_via_container()
    {
        // when
        $mailer = $this->app->make(Mailer::class);

        // then
        $this->assertInstanceOf(Mailer::class, $mailer);
    }
}
