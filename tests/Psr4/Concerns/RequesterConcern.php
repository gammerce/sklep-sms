<?php
namespace Tests\Psr4\Concerns;

use App\Requesting\Requester;
use Mockery;
use Mockery\MockInterface;

trait RequesterConcern
{
    /** @var Requester|MockInterface */
    public $requesterMock;

    public function mockRequester()
    {
        $this->requesterMock = Mockery::mock(Requester::class);
        $this->app->instance(Requester::class, $this->requesterMock);
    }
}
