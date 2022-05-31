<?php
namespace Tests\Unit\Support;

use App\Support\BasePath;
use PHPUnit\Framework\TestCase;
use Tests\Psr4\Concerns\SystemConcern;

class BasePathTest extends TestCase
{
    use SystemConcern;

    private BasePath $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = new BasePath("/test");
    }

    /** @test */
    public function it_returns_absolute_path_for_absolute_path()
    {
        // when
        $absolutePath = $this->path->to("/foo/bar.sql");

        // then
        $this->assertSame($this->path->to("/foo/bar.sql"), $absolutePath);
    }

    /** @test */
    public function it_returns_absolute_path_for_relative_path()
    {
        // when
        $absolutePath = $this->path->to("foo/bar.sql");

        // then
        $this->assertSame($this->path->to("/foo/bar.sql"), $absolutePath);
    }

    public function testTemporary()
    {
        // given
        $base = BasePath::temporary();
        // when
        $path = $base->to("");
        // then
        if ($this->isUnix()) {
            $this->assertStringContainsString("tmp", $path);
        } else {
            $this->assertStringContainsString("Temp", $path);
        }
    }

    public function testSubpath()
    {
        // given
        $path = new BasePath("/a/b/c");
        // when
        $this->assertSameWindows("/a/b/c\d/e", $path->to("d/e"));
        $this->assertSameUnix("/a/b/c/d/e", $path->to("d/e"));
    }
}
