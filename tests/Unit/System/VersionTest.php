<?php
namespace Tests\Unit\System;

use Tests\Psr4\TestCases\TestCase;

class VersionTest extends TestCase
{
    /** @test */
    public function version_is_ok()
    {
        $this->assertEquals("3.16.2", $this->app->version());
    }
}
