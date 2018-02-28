<?php
namespace Tests\Psr4\Concerns;

use App\Requester;
use Mockery\MockInterface;

trait RequesterConcern
{
    /** @var Requester|MockInterface */
    private $requesterMock;

    protected function mockRequester()
    {
        $requester = $this->app->make(Requester::class);
        $this->requesterMock = \Mockery::mock($requester)->makePartial();
        $this->app->instance(Requester::class, $this->requesterMock);
    }
}
