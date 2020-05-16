<?php
namespace App\Payment\General;

use MyCLabs\Enum\Enum;

/**
 * @method static PaymentResultType PURCHASED()
 * @method static PaymentResultType EXTERNAL()
 */
final class PaymentResultType extends Enum
{
    const PURCHASED = "purchased";
    const EXTERNAL = "external";
}
