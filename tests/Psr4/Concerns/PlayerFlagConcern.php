<?php
namespace Tests\Psr4\Concerns;

use App\ServiceModules\ExtraFlags\PlayerFlag;

trait PlayerFlagConcern
{
    public function assertPlayerFlags($expected, $actual): void
    {
        $fullExpected = array_merge(array_fill_keys(PlayerFlag::FLAGS, 0), $expected);

        foreach (PlayerFlag::FLAGS as $flag) {
            $expectedValue = $fullExpected[$flag];
            $actualValue = $actual[$flag];

            if (in_array($expectedValue, [-1, 0], true) || in_array($actualValue, [-1, 0], true)) {
                $this->assertSame($expectedValue, $actualValue, "Failed asserting flag $flag.");
            } else {
                $this->assertAlmostSameTimestamp($expectedValue, $actualValue);
            }
        }
    }
}
