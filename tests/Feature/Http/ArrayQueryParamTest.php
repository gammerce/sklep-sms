<?php
namespace Tests\Feature\Http;

use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class ArrayQueryParamTest extends HttpTestCase
{
    /** @test */
    public function it_handles_array_query_parameters()
    {
        $response = $this->get("/", [
            "platforms" => ["sourcemod"],
            "amount" =>
                "📊 Transfer 236,538 $. GET ->> graph.org/BALANCE-3682444-USD-04-21-2?hs=65b37b7e4f3e8d19facd05e1ea79ca1f& 📊",
            "subdomain" => "x68sld",
            "email" => "v4yc92xhbbuyca@web-library.net",
            "service_id" => "ss_license",
        ]);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
