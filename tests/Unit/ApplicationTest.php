<?php
namespace Tests\Unit;

use Tests\TestCase;

class ApplicationTest extends TestCase
{
    /** @test */
    public function version_is_341()
    {
        $this->assertEquals('3.4.1', VERSION);
    }
}
