<?php
namespace Tests\Unit\Support;

use App\Support\Meta;
use Tests\Psr4\TestCases\TestCase;

class MetaTest extends TestCase
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
        $this->assertEquals("3.16.2", $this->meta->getVersion());
    }

    /** @test */
    public function build_is_ok()
    {
        $this->assertEquals("dev", $this->meta->getBuild());
    }
}
