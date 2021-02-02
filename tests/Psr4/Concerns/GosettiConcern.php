<?php
namespace Tests\Psr4\Concerns;

use App\Requesting\Response;

trait GosettiConcern
{
    protected function mockGoSettiGetData(): void
    {
        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs(["https://gosetti.pl/Api/SmsApiV2GetData.php"])
            ->andReturn(
                new Response(
                    200,
                    json_encode([
                        "Code" => "abc123",
                        "Numbers" => [],
                    ])
                )
            );
    }
}
