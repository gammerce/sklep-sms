<?php
namespace Tests\Unit\Support;

use App\Support\Path;
use Tests\Psr4\TestCases\TestCase;
use Tests\Psr4\TestCases\UnitTestCase;

class PathTest extends UnitTestCase
{
    private Path $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = new Path("/test");
    }

    /** @test */
    public function it_returns_absolute_path_for_relative_path_prefixed_with_slash()
    {
        // given
        $relativePath = "/foo/bar.sql";

        // when
        $absolutePath = $this->path->to($relativePath);

        // then
        $this->assertEquals($this->path->to() . "/foo/bar.sql", $absolutePath);
    }

    /** @test */
    public function it_returns_absolute_path_for_relative_path_not_prefixed_with_slash()
    {
        // given
        $relativePath = "foo/bar.sql";

        // when
        $absolutePath = $this->path->to($relativePath);

        // then
        $this->assertEquals($this->path->to() . "/foo/bar.sql", $absolutePath);
    }
}
