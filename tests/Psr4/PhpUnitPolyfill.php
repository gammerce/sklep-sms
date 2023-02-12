<?php
namespace Tests\Psr4;

use PHPUnit\Framework\Assert;

trait PhpUnitPolyfill
{
    public static function assertStringContainsString(
        string $needle,
        string $haystack,
        string $message = ""
    ): void {
        $message = $message ?? "Failed to assert that starting contains substring";
        Assert::assertTrue(\str_contains($haystack, $needle), $message);
    }
}
