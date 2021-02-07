<?php
namespace App\License;

use App\Server\Platform;

class LicensePriceService
{
    // Costs per day in grosze
    const COST_SHOP_PER_DAY = 40;
    const COST_PLATFORM_PER_DAY = 20;

    /**
     * @param int $costDaily
     * @param int $daysAmount
     * @return int
     */
    public function getCost($costDaily, $daysAmount): int
    {
        return (int) ceil($costDaily * $daysAmount * $this->getBargain($daysAmount));
    }

    /**
     * Calculate license daily cost
     *
     * @param Platform[] $platforms
     * @return int
     */
    public function getDailyCost(array $platforms): int
    {
        // -1, because the first platform is free
        $platformsCost = max(0, (count($platforms) - 1) * self::COST_PLATFORM_PER_DAY);
        return (int) ceil(self::COST_SHOP_PER_DAY + $platformsCost);
    }

    public function getBargainPercentage($daysCount): int
    {
        if ($daysCount >= 365) {
            return 20;
        }

        return 0;
    }

    public function getBargain($daysCount): float
    {
        return (100 - $this->getBargainPercentage($daysCount)) / 100;
    }
}
