<?php
namespace Tests\Unit\Application;

use Tests\Psr4\TestCase;

class VersionTest extends TestCase
{
    /** @test */
    public function version_is_341()
    {
        $this->assertEquals('3.4.0', $this->app->version());
    }
}
