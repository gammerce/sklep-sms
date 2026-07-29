<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

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
