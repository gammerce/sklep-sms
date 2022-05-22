<?php
namespace Tests\Unit\Support;

use App\Support\Meta;
use Tests\Psr4\TestCases\TestCase;
use Tests\Psr4\TestCases\UnitTestCase;

class MetaTest extends UnitTestCase
{
    private Meta $meta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->meta = $this->app->make(Meta::class);
    }

    /** @test */
    public function version_is_ok()
    {
        $this->assertEquals("3.19.0", $this->meta->getVersion());
    }

    /** @test */
    public function build_is_ok()
    {
        $this->assertEquals("dev", $this->meta->getBuild());
    }
}
