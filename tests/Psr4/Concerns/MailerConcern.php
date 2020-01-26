<?php
namespace Tests\Psr4\Concerns;

use App\System\Mailer;

trait MailerConcern
{
    public function mockMailer()
    {
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive("send")->andReturn("sent");
        $this->app->instance(Mailer::class, $mailer);
    }
}
