<?php
namespace Tests\Psr4\Concerns;

use App\Requesting\Response;
use Mockery;

trait SimPayConcern
{
    public function mockSimPayIpList(): void
    {
        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs(["https://simpay.pl/api/get_ip"])
            ->andReturn(
                new Response(
                    200,
                    json_encode([
                        "respond" => [
                            "ips" => [],
                        ],
                    ])
                )
            );
    }

    public function mockSimPayApiSuccessResponse(): void
    {
        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs(["https://simpay.pl/db/api", Mockery::any()])
            ->andReturnUsing(
                fn($url, $body) => new Response(
                    200,
                    json_encode([
                        "status" => "success",
                        "link" => "https://example.com",
                    ])
                )
            );
    }
}
