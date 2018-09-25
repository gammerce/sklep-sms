<?php
namespace Tests\Psr4\Concerns;

use App\Requesting\Requester;
use Mockery\MockInterface;

trait RequesterConcern
{
    /** @var Requester|MockInterface */
    private $requesterMock;

    /** @before */
    public function mockRequester()
    {
        $requester = $this->app->make(Requester::class);
        $this->requesterMock = \Mockery::mock($requester)->makePartial();
        $this->app->instance(Requester::class, $this->requesterMock);
    }
}
