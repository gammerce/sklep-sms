<?php
namespace Tests\Psr4\Concerns;

use App\Requesting\Response;

trait CssettiConcern
{
    protected function mockCSSSettiGetData()
    {
        $this->requesterMock
            ->shouldReceive('get')
            ->withArgs(['https://cssetti.pl/Api/SmsApiV2GetData.php'])
            ->andReturn(
                new Response(
                    200,
                    json_encode([
                        'Code' => 'abc123',
                        'Numbers' => [],
                    ])
                )
            );
    }
}
