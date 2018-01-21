<?php
namespace Tests\Unit;

use Tests\TestCase;

class ApplicationTest extends TestCase
{
    /** @test */
    public function version_is_340()
    {
        $this->assertEquals('3.4.0', VERSION);
    }
}
