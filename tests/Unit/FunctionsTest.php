<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class FunctionsTest extends TestCase
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
    public function decodes_string_query_parameters()
    {
        $_GET = [
            "service_id" => "ss_license",
            "email" => "v4yc92xhbbuyca%40web-library.net",
        ];

        $request = \captureRequest();

        $this->assertSame("ss_license", $request->query->get("service_id"));
        $this->assertSame("v4yc92xhbbuyca@web-library.net", $request->query->get("email"));
    }

    /** @test */
    public function decodes_array_query_parameters()
    {
        $_GET = [
            "platforms" => ["sourcemod"],
            "amount" => "%F0%9F%93%8A+Transfer",
        ];

        $request = \captureRequest();

        $this->assertSame(["sourcemod"], $request->query->all()["platforms"]);
        $this->assertSame("📊 Transfer", $request->query->get("amount"));
    }
}
