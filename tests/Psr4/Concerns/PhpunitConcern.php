<?php
namespace Tests\Psr4\Concerns;

use Exception;

/**
 * @method expectException(string $message)
 */
trait PhpunitConcern
{
    /**
     * This test really is marked as unnecessary. If the condition is not met,
     * marking it as unnecessary is preferable to marking it as risky, incomplete
     * or skipped.
     *
     * This test simply doesn't make sense, if the condition is not met.
     */
    public function markTestUnnecessary(string $message): void
    {
        $this->expectException(Exception::class);
        throw new Exception($message);
    }
}
