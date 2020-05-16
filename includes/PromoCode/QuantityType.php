<?php
namespace App\PromoCode;

use MyCLabs\Enum\Enum;

/**
 * @method static QuantityType PERCENTAGE()
 * @method static QuantityType FIXED()
 */
final class QuantityType extends Enum
{
    const PERCENTAGE = "percentage";
    const FIXED = "fixed";
}
