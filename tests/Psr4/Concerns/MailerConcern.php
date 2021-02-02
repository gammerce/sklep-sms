<?php
namespace Tests\Psr4\Concerns;

use App\Support\Mailer;
use Mockery;

trait MailerConcern
{
    public function mockMailer(): void
    {
        $mailer = Mockery::mock(Mailer::class);
        $mailer
            ->shouldReceive("send")
            ->andReturn("sent")
            ->byDefault();
        $this->app->instance(Mailer::class, $mailer);
    }
}
