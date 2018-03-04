<?php
namespace Tests\Unit\Application;

use Tests\Psr4\TestCases\TestCase;

class PathTest extends TestCase
{
    /** @test */
    public function it_returns_absolute_path_for_relative_path_prefixed_with_slash()
    {
        // given
        $relativePath = '/foo/bar.sql';

        // when
        $absolutePath = $this->app->path($relativePath);

        // then
        $this->assertEquals($this->app->path() . '/foo/bar.sql', $absolutePath);
    }

    /** @test */
    public function it_returns_absolute_path_for_relative_path_not_prefixed_with_slash()
    {
        // given
        $relativePath = 'foo/bar.sql';

        // when
        $absolutePath = $this->app->path($relativePath);

        // then
        $this->assertEquals($this->app->path() . '/foo/bar.sql', $absolutePath);
    }
}
