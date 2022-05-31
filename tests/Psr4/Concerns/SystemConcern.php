<?php
namespace Tests\Psr4\Concerns;

use PHPUnit\Framework\Assert;

trait SystemConcern
{
    public function assertSameWindows($expected, $actual): void
    {
        if (!$this->isUnix()) {
            Assert::assertSame($expected, $actual);
        }
    }

    public function assertSameUnix($expected, $actual): void
    {
        if ($this->isUnix()) {
            Assert::assertSame($expected, $actual);
        }
    }

    public function isUnix(): bool
    {
        return \DIRECTORY_SEPARATOR === "/";
    }
}
