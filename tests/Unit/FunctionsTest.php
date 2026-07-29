<?php
namespace Tests\Unit;

use App\Kernels\KernelContract;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\UnitTestCase;

class FunctionsTest extends UnitTestCase
{
    private array $originalGet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalGet = $_GET;
    }

    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        parent::tearDown();
    }

    /** @test */
    public function request_with_array_query_does_not_crash()
    {
        $_GET = [
            "platforms" => ["sourcemod"],
            "amount" =>
                "📊 Transfer 236,538 $. GET ->> graph.org/BALANCE-3682444-USD-04-21-2?hs=65b37b7e4f3e8d19facd05e1ea79ca1f& 📊",
            "subdomain" => "x68sld",
            "email" => "v4yc92xhbbuyca@web-library.net",
            "service_id" => "ss_license",
        ];

        $request = \captureRequest();

        /** @var KernelContract $kernel */
        $kernel = $this->app->make(KernelContract::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(["sourcemod"], $request->query->all()["platforms"]);
        $this->assertStringContainsString(
            "📊 Transfer 236,538 $. GET ->> graph.org/BALANCE-3682444-USD-04-21-2?hs=65b37b7e4f3e8d19facd05e1ea79ca1f& 📊",
            $request->query->get("amount")
        );
    }
}
