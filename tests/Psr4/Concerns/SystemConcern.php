<?php
namespace Tests\Psr4\Concerns;

use App\Support\Path;
use PHPUnit\Framework\Assert;

trait SystemConcern
{
    public function assertPathWindows(string $expected, Path $actual): void
    {
        $this->assertPath($expected, $actual, "\\");
    }

    public function assertPathUnix(string $expected, Path $actual): void
    {
        $this->assertPath($expected, $actual, "/");
    }

    private function assertPath(string $expected, Path $actual, string $separator): void
    {
        Assert::assertSame($expected, $actual->toString($separator));
    }

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
