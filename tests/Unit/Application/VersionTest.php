<?php
namespace Tests\Unit\Application;

use Tests\Psr4\TestCases\TestCase;

class VersionTest extends TestCase
{
    /** @test */
    public function version_is_350()
    {
        $this->assertEquals('3.5.1', $this->app->version());
    }
}
