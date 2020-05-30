<?php
namespace Tests\Psr4\Concerns;

use App\Requesting\Requester;
use App\Requesting\Response as RequestingResponse;
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

        $this->mockExternalConfig();
    }

    private function mockExternalConfig()
    {
        $this->requesterMock->shouldReceive("get")
            ->withArgs(["https://license.sklep-sms.pl/config", Mockery::any(), Mockery::any()])
            ->andReturn(new RequestingResponse(
                200,
                json_encode([])
            ));
    }
}
